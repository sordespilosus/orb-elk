<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\{GetNotificationPrintRequest, GetNotificationsRequest};
use App\Http\Traits\{OrganizationsTrait, OuterStorageTrait};
use Carbon\Carbon;
use GuzzleHttp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Lang, Log};
use SimpleXMLElement;

class NotificationsController extends Controller
{
    use OuterStorageTrait;
    use OrganizationsTrait;

    private $requestFilesParams;

    public function __construct()
    {
        $this->requestFilesParams = [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(env('FILE_STORAGE_LOGIN') . ':' . env('FILE_STORAGE_PASSWORD'))
            ]
        ];
    }

    public function getNotificationsPage(Request $request)
    {
        return response()->view('my_notifications');
    }

    public function getNotifications(GetNotificationsRequest $request): array
    {
        $page = $request->post('page', 1);
        $perPage = $request->post('per_page', 10000);
        $orgId = $request->post('org_id');
        $status = $request->post('status');
        $service = $request->post('service');
        $orderBy = $request->post('order_by', 'show_number');
        $isAsc = $request->post('is_asc', false);

        $organizations = $this->getOrganizationsUser(null, false);

        $ogrn = null;

        if (count($organizations) > 0) {
            if ($orgId != null) {
                foreach ($organizations as $organization) {
                    if ($orgId == $organization->id) {
                        $ogrn = $organization->ogrn;
                        break;
                    }
                }
            }
            array_unshift(
                $organizations,
                (object)['id' => null, 'short_name' => Auth::user()->getAttribute('fio')]
            );
        }
        $sort = '-id';

        if ($orderBy == 'show_number') {
            $sort = $isAsc ? '+id' : '-id';
        }

        $optionsRequest = [
            'query' => [
                'declarant_rsaag_id' => Auth::id(),
                'limit' => $perPage,
                'offset' => ($page - 1) * $perPage,
                //'status_code' => $status, //TODO:: закомментил эти две строки в силу ручных фильтров :)
                //'service_code' => $service,
                'sort' => $sort
            ]
        ];
        if ($ogrn) {
            unset($optionsRequest['query']['declarant_rsaag_id']);
            $optionsRequest['query']['declarant_ogrn'] = $ogrn;
        }
        try {
            $content = $this->getOuterData(env('OUTER_STORAGE') . env('OUTER_STORAGE_V') . '/applications', $optionsRequest);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), $optionsRequest);
            $content = null;
        }

        if (!$content) {
            return ['status' => false];
        } elseif ($content === 404) {
            $content = (object)['items' => [], 'meta' => (object)['count' => 0]];
        }

        $notificationsData = $content->items;
        $meta = $content->meta;
        $notifications = [];
        foreach ($notificationsData as $notificationData) {
            $notifications[] = [
                'service_number' => $notificationData->service_number,
                'number' => isset($notificationData->reg_number) ? $notificationData->reg_number : $notificationData->service_number,
                'service_name' => isset($notificationData->service_name) ? $notificationData->service_name : '',
                'summary' => isset($notificationData->summary) ? $notificationData->summary : null,
                'service_code' => (string)$notificationData->service_code,
                'status_name' => $notificationData->status_name,
                'status_date' => date('d.m.Y H:i ', strtotime($notificationData->status_date)),
                'status_id' => (string)$notificationData->status_code,
                'reg_service_date' => date('d.m.Y H:i', strtotime($notificationData->reg_service_date)),
            ];
        }

        $resultsTotal = ceil($meta->count / $perPage);
        if ($page == 1) {
            $soapData = $this->getSoapData();
            if ($soapData) {
                $notifications = array_merge($notifications, $soapData);
            }
            usort($notifications, function ($a, $b) use ($isAsc) {
                $res = strtotime($a['reg_service_date']) < strtotime($b['reg_service_date']) ? 1 : 0;
                if ($isAsc) {
                    $res = (int)!(bool)$res;
                }
                return $res;
            });
            $statuses = [];
            $services = [];
            foreach ($notifications as $k => $notify) {
                if (!isset($statuses[$notify['status_id']])) {
                    $statuses[$notify['status_id']] = [
                        'number' => (string)$notify['status_id'],
                        'name' => $notify['status_name']
                    ];
                }
                if (!isset($services[$notify['service_code']])) {
                    $services[$notify['service_code']] = [
                        'number' => (string)$notify['service_code'],
                        'name' => $notify['service_name']
                    ];
                }
            }
            foreach ($notifications as $k => $notify) {
                if (($service && $service !== $notify['service_code']) || ($status && $status !== $notify['status_id'])) {
                    unset($notifications[$k]);
                }
            }
        }
        return [
            'status' => true,
            'data' => [
                'notifications' => array_values($notifications),
                'resultsTotal' => $resultsTotal ? $resultsTotal : 1,
                'statuses' => array_values($statuses),
                'services' => array_values($services),
                'organizations' => array_values($organizations)
            ]
        ];
    }

    public function getNotificationPrint(GetNotificationPrintRequest $request, $serviceNumber)
    {
        $body_id = $request->get('body_id');

        if (!$body_id) {
            return ['status' => false];
        }

        try {
            $info = $this->getOuterData(env('OUTER_STORAGE') . env('OUTER_STORAGE_V') . '/applications/' . $serviceNumber);
            $content = $this->getOuterData(env('OUTER_STORAGE') . env('OUTER_STORAGE_V') . '/applications/' . $serviceNumber . '/bodies/' . $body_id);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['serviceNumber' => $serviceNumber]);
            $content = null;
        }

        if (!isset($info) || $info === 404) {
            $trySoap = $this->getSoapData($serviceNumber);
            if ($trySoap) {
                $info = $trySoap['note'];
            } else {
                return ['status' => false];
            }
        }

        $info->reg_service_date = date('d.m.Y H:i', strtotime($info->reg_service_date));

        return response()->view('notification_print', ['content' => $content, 'info' => $info]);
    }

    public function getNotification(GetNotificationPrintRequest $request, $serviceNumber)
    {
        $bodyId = null;

        try {
            $content = $this->getOuterData(env('OUTER_STORAGE') . env('OUTER_STORAGE_V') . '/applications/' . $serviceNumber);

            if ($content->service_code && $content->service_code == '00085') {
                $info = $this->getOuterData(env('OUTER_STORAGE') . env('OUTER_STORAGE_V') . '/applications/' . $serviceNumber . '/bodies?system_code=00001');
                $info = array_shift($info);
                if ($info && $info->body_id) {
                    $bodyId = $info->body_id;
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['serviceNumber' => $serviceNumber]);
            $content = null;
        }

        if (!$content || $content === 404) {
            $trySoap = $this->getSoapData($serviceNumber);
            if ($trySoap) {
                return [
                    'status' => true,
                    'data' => [
                        'notification' => $trySoap['note'],
                        'statusHistory' => array_reverse($trySoap['statuses'])
                    ]
                ];
            }
            return ['status' => false];
        }

        $statusHistory = $content->status_history;
        $notification = [
            [
                'name' => 'Дата формирования заявления',
                'value' => date('d.m.Y H:i', strtotime($content->reg_service_date)),
            ],
            [
                'name' => 'Единый номер обращения',
                'value' => $serviceNumber,
            ],
            [
                'name' => 'Наименование услуги',
                'value' => $content->service_name
            ]
        ];
        if (isset($content->summary)) {
            $notification[] = [
                'name' => 'Описание услуги',
                'value' => $content->summary
            ];
        }
        foreach ($statusHistory as $j => &$status) {
            $status->raw_status_date = $status->status_date;
            $status->status_date = date('d.m.Y H:i', strtotime($status->status_date));
            if (isset($status->documents)) {
                foreach ($status->documents as $k => $doc) {
                    $files = [];
                    foreach ($doc->files as $file) {
                        $files[] = [
                            'file_name' => $file->name,
                            'file_link' => "/my_notifications/file/{$file->store_id}?store={$file->store_name}",
                        ];
                        if (!!$this->getFileSigns($file->store_id)) {
                            $key = count($files) - 1;
                            $files[$key]['file_sign'] = "/my_notifications/file/{$file->store_id}?store={$file->store_name}&sign=true";
                        }
                    }
                    if (count($files) === 0) continue;
                    $statusHistory[$j]->docs[] = [
                        'doc_code' => $doc->document_code,
                        'files' => $files
                    ];
                }
            }
            unset($status->documents);
        }

        usort($statusHistory, function ($a, $b) {
            $aTime = Carbon::parse($a->raw_status_date);
            $bTime = Carbon::parse($b->raw_status_date);
            if ($aTime->equalTo($bTime)) {
                if ($a->status_code == $b->status_code) return 0;
                return $a->status_code < $b->status_code ? 1 : -1;
            }
            return $aTime->lessThan($bTime) ? 1 : -1;
        });

        return ['status' => true, 'data' => ['notification' => $notification, 'statusHistory' => array_reverse($statusHistory), 'body_id' => $bodyId]];
    }

    public function getSoapData($service_number = null)
    {
        $user = Auth::user();
        $passport = (array)$user->passport;
        $fromEsia = (array)$user->esia_info;
        if (!$passport || !$fromEsia) {
            return false;
        }
        $deprecatedStructure = false;
        $fieldsCounter = 0;
        foreach ($fromEsia as $k => $attr) {
            if (is_numeric($k)) $deprecatedStructure = true;
            if ($deprecatedStructure && in_array($attr, ['name', 'surname', 'patroname', 'passport'])) {
                $fieldsCounter += 1;
            } elseif (in_array($k, ['name', 'surname', 'patroname', 'passport'])) {
                $fieldsCounter += 1;
            }
        }
        if ($fieldsCounter === 4) {
            try {
                $passport = explode(' ', $passport['number']);
                $series = $passport[0];
                $number = $passport[1];
                if ($deprecatedStructure) {
                    $name = $user->name;
                    $surname = $user->surname;
                    $patroname = $user->patroname;
                } else {
                    $name = $fromEsia['name'];
                    $surname = $fromEsia['surname'];
                    $patroname = $fromEsia['patroname'];
                }
                $xml = "
            <soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">
                <soapenv:Header/>
                <soapenv:Body>
                    <tem:ApplicationListByPassport>
                        <tem:request>
                            <header>
                                <sender>rpgu</sender>
                            </header>
                            <lastName>$surname</lastName>
                            <firstName>$name</firstName>
                            <middleName>$patroname</middleName>
                            <series>$series</series>
                            <number>$number</number>
                            <pageSize>10000</pageSize>
                            <pageNumber>1</pageNumber>
                        </tem:request>
                    </tem:ApplicationListByPassport>
                </soapenv:Body>
            </soapenv:Envelope>";
                $headers = [
                    'Content-type: text/xml;charset="utf-8"',
                    'Accept: text/xml',
                    'Cache-Control: no-cache',
                    'Pragma: no-cache',
                    'SOAPAction: http://tempuri.org/IIntegrationController/ApplicationListByPassport',
                    'Content-length: ' . strlen($xml),
                ];
                $url = "http://172.17.101.231:7783/IntegrationService?singleWsdl";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $answer = curl_exec($ch);
                curl_close($ch);
                $answer = str_replace('</ApplicationListByPassportResponse></s:Body></s:Envelope>', '', strstr($answer, '<ApplicationListByPassportResult>'));
                $var = json_decode(json_encode(new SimpleXMLElement($answer)));
                $soap = [];
                foreach ($var->application as $status) {
                    $soap[$status->serviceId][] = $status;
                }
                //Часть, где мы формируем массив для общего списка
                if (!$service_number) {
                    foreach ($soap as $k => $item) {
                        foreach ($item as $status)
                            $soap[$k] = [
                                'service_code' => $status->serviceId,
                                'service_number' => $status->serviceId,
                                'show_number' => $status->serviceId,
                                'number' => $status->serviceId,
                                'service_name' => $status->serviceName,
                                'status_name' => $status->status,
                                'status_date' => date('d.m.Y', strtotime($status->statusDate)),
                                'status_id' => $status->statusId,
                                'reg_service_date' => date('d.m.Y', strtotime($status->applicationDate)),
                            ];
                    }
                    return $soap;
                }
                //Часть, где мы формируем объект для детализации
                if ($service_number && array_values($soap)[0][0]->serviceId == $service_number) {
                    $notification = [
                        [
                            'name' => 'Дата формирования заявления',
                            'value' => date('d.m.Y', strtotime(array_values($soap)[0][0]->applicationDate)),
                        ],
                        [
                            'name' => 'Единый номер обращения',
                            'value' => $service_number,
                        ],
                        [
                            'name' => 'Наименование услуги',
                            'value' => array_values($soap)[0][0]->serviceName
                        ]
                    ];
                    foreach ($soap as $item) {
                        if ($item[0]->serviceId != $service_number) continue;
                        foreach ($item as $status) {
                            if (is_string($status->statusInfo)) {
                                $note = "$status->statusInfo";
                            }
                            if (isset($status->declineReason) && is_string($status->declineReason)) {
                                $note .= " ($status->declineReason)";
                            }
                            $statuses[] = [
                                'status_date' => date('d.m.Y', strtotime($status->statusDate)),
                                'status_name' => $status->status,
                                'note' => $note
                            ];
                        }

                    }
                    usort($statuses, function ($a, $b) {
                        return strtotime($a['status_date']) > strtotime($b['status_date']) ? 1 : 0;
                    });
                    return [
                        'note' => $notification,
                        'statuses' => $statuses
                    ];
                }
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    public function getNotificationFile($file_id, Request $request)
    {
        $needSign = $request->get('sign', null);
        try {
            if ($needSign) {
                return $this->returnFileSigns($file_id);
            }
            $http = new GuzzleHttp\Client(array(
                'verify' => false
            ));
            $answer = $http->get(env('FILE_STORAGE_URL') . "/custom-api-disgu/rest/api/document/{$file_id}/data", $this->requestFilesParams);
            $file = $answer->getBody()->getContents();

            return response($file)->withHeaders($answer->getHeaders());
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['file_id' => $file_id]);
            $errors = Lang::get('errors.51');
            return response()->view("errors/error", [
                'errors' => [$errors],
            ], 400);
        }
    }

    public function returnFileSigns($file_id)
    {
        $signs = $this->getFileSigns($file_id);
        if (!count($signs)) {
            abort(404);
        }
        $http = new GuzzleHttp\Client(array(
            'verify' => false
        ));
        $answer = $http->get(env('FILE_STORAGE_URL') . "/custom-api-disgu/rest/api/signature/{$file_id}/{$signs[0]}/data", $this->requestFilesParams);
        $sign = $answer->getBody()->getContents();
        $headers = $answer->getHeaders();
        $headers['Content-Disposition'] = "attachment;filename={$file_id}+{$signs[0]}.sig";
        return response($sign)->withHeaders($headers);
    }

    private function getFileSigns($file_id)
    {
        return $this->getOuterData(env('FILE_STORAGE_URL') . "/custom-api-disgu/rest/api/document/{$file_id}/signatures", $this->requestFilesParams);
    }
}
