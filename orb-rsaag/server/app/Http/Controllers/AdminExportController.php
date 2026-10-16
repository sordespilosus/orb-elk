<?php

namespace App\Http\Controllers;

use App\Exports\{LogsExport, UsersExport};
use App\Services\AdminUsersQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AdminExportController extends Controller
{
    /**
     * @OA\Get(path="/admin/api/users/export", tags={"Export"},
     *     summary="Экспорт списка пользователей в файл .xlsx или .csv",
     *     @OA\Parameter(name="mode", in="query", description="Формат выходного файла", required=true,
     *         @OA\Schema(type="string", enum={"xlsx", "csv"}, default="csv")
     *     ),
     *     @OA\Parameter(name="order_by", in="query", description="Столбец сортировки", required=false,
     *         @OA\Schema(type="string", default="oauth.users.id")
     *     ),
     *     @OA\Parameter(name="is_asc", in="query", description="Направление сортировки", required=false,
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Parameter(name="fio", in="query", description="%ФИО пользователя%", required=false,
     *         @OA\Schema(type="string", default=null)
     *     ),
     *     @OA\Parameter(name="first_name", in="query", description="%Имя пользователя%", required=false,
     *         @OA\Schema(type="string", default=null)
     *     ),
     *     @OA\Parameter(name="second_name", in="query", description="%Фамилия пользователя%", required=false,
     *         @OA\Schema(type="string", default=null)
     *     ),
     *     @OA\Parameter(name="patroname", in="query", description="%Отчество пользователя%", required=false,
     *         @OA\Schema(type="string", default=null)
     *     ),
     *     @OA\Parameter(name="city", in="query", description="Город (код)", required=false,
     *         @OA\Schema(type="integer", default=null)
     *     ),
     *     @OA\Parameter(name="gender", in="query", description="Пол пользователя", required=false,
     *         @OA\Schema(type="string", enum={"male", "female"}, default=null)
     *     ),
     *     @OA\Parameter(name="age_period_from", in="query",
     *         description="Возраст пользователя от",
     *         required=false,
     *         @OA\Schema(type="integer", default=null)
     *     ),
     *     @OA\Parameter(name="age_period_to", in="query",
     *         description="Возраст пользователя до",
     *         required=false,
     *         @OA\Schema(type="integer", default=null)
     *     ),
     *     @OA\Parameter(name="id", in="query", description="ID пользователя", required=false,
     *         @OA\Schema(type="integer", default=null)
     *     ),
     *     @OA\Parameter(name="is_blocked", in="query", description="Заблокирован ли пользователь", required=false,
     *         @OA\Schema(type="boolean", default=null)
     *     ),
     *     @OA\Parameter(name="is_accepted", in="query", description="Подтвержден ли пользователь", required=false,
     *         @OA\Schema(type="boolean", default=null)
     *     ),
     *     @OA\Response(response=200, description="Файл в формате .xlsx или .csv"),
     *     @OA\Response(response=400, description="Ошибка параметра mode",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string",
     *                 example="Параметр 'mode' может принимать значения только 'csv' или 'xlsx'"
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportUsers(Request $request)
    {
        try {
            $mode = $this->validateMode($request);
        } catch (\Exception $e) {
            return response()->json(['errors' => json_decode($e->getMessage())], 400);
        }

        $orderBy = $request->get('order_by', 'id');
        $isAsc = $request->get('is_asc', true);
        $query = AdminUsersQuery::selectExportColumns(
            AdminUsersQuery::filtered($request->all(), true)
        );

        AdminUsersQuery::applyOrder($query, $orderBy, $isAsc, [
            'area_name' => 'da.area_name',
            'admin' => 'admin',
            'email_verified' => 'email_verified'
        ]);

        return Excel::download(new UsersExport($query), "users.$mode");
    }

    /**
     * @OA\Get(path="/admin/api/export_logs", tags={"Export"}, summary="Экспорт списка логов в формате .xlsx или .csv",
     *     @OA\Parameter(name="mode", in="query", description="Формат выходного файла", required=true,
     *         @OA\Schema(type="string", enum={"xlsx", "csv"}, default="csv")
     *     ),
     *     @OA\Parameter(name="order_by", in="query", description="Столбец сортировки", required=false,
     *         @OA\Schema(type="string", enum={"id", "ip", "method", "date_created", "url"}, default="id")
     *     ),
     *     @OA\Parameter(name="is_asc", in="query", description="Направление сортировки", required=false,
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Response(response=200, description="Файл в формате .xlsx или .csv"),
     *     @OA\Response(response=400, description="Ошибка параметра mode",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string",
     *                 example="Параметр 'mode' может принимать значения только 'csv' или 'xlsx'"
     *             )
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportLogs(Request $request)
    {
        try {
            $mode = $this->validateMode($request);
        } catch (\Exception $e) {
            return response()->json(['errors' => json_decode($e->getMessage())], 400);
        }

        $orderBy = $request->get('order_by', 'id');
        $isAsc = $request->get('is_asc', false);

        return Excel::download(new LogsExport($orderBy, $isAsc), "logs.$mode");
    }

    /**
     * Валидация параметра "mode" (расширение выходного файла)
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    private function validateMode(Request $request)
    {
        $mode = $request->get('mode', 'csv');
        $validator = Validator::make(compact('mode'), [
            'mode' => [
                'required',
                Rule::in(['csv', 'xlsx'])
            ]
        ],
            ['mode.in' => 'Параметр mode может принимать значения только "csv" или "xlsx"']
        );

        if ($validator->fails()) {
            throw new \Exception($validator->errors(), 400);
        }

        return $mode;
    }

}
