<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title></title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500&subset=cyrillic" rel="stylesheet">
</head>
<body style="margin: 0;padding: 0;" topmargin="0" leftmargin="0" rightmargin="0" bottommargin="0">

<div style="width: 600px; margin: 0 auto; padding: 0 40px 0 40px; background: #f9f9f9; border-radius: 25px; font-size: 1.1em; color: #000000;">
    <div style="padding: 20px 0 20px 0;">
        <div style="margin: 30px 0 0 0">
            <table cellspacing="0" cellpadding="0">
                <tr>
                    <td width="38" style="width:38px;">
                        <img width="30" height="30"
                             src="https://cabinet.permkrai.ru/images/rs2.png"
                             alt="Логотип ГИС ЕЛК ОО" style="border: none; background-repeat: no-repeat; background-size: cover; background-position: center bottom;" border="0">
                    </td>
                    <td>
                        <div style="font-size: 1.2em; color: #000000; line-height: 1.2em;">
                            Региональный сервис аутентификации и авторизации гражданина
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <hr style="border-top: 1.5px solid rgba(0,0,0,.1)" size="2" color="#455566" width="100%" />

        <div style="margin: 20px 0">
            Уважаемый пользователь!
        </div>

        <div style="margin: 20px 0; line-height: 26px;">
                От вас поступил запрос на изменение адреса электронной почты учетной записи в <a href="{{ url('/') }}">ГИС ЕЛК ОО</a>.
        </div>

        <div style="line-height: 26px;" >Для подтверждения нового адреса перейдите  <a href='{{$link}}' target="_blank">по ссылке</a>:<br>
            <table border="0" cellpadding="0" cellspacing="0" style="margin: 20px auto; background-color: #1cceab; width: 320px; text-align: center;">
                <tr style='height: 50px;'>
                    <td align="center">
                        <table style='text-align: center; height: 100%; width: 100%;' border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td> <a style="display: block; font-family: 'Roboto'; text-decoration: none; color: #fff; text-transform: uppercase;" href="{{$link}}" target="_blank">Подтвердить</a></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table></div>

        <hr size="1" color="#e6e6e6" style="height:1px;" />

        <div style="margin:0px 0 0px 0; padding: 10px 0 0 0; line-height: 26px;">
            С уважением, <br>
            Команда проекта <a href="{{ url('/') }}">«ГИС ЕЛК ОО»</a>
        </div>

        <div style="margin:20px 0; color: #7e888c; line-height: 26px;">
            Пожалуйста, не отвечайте на это письмо, так как оно сгенерировано автоматически.
        </div>
    </div>

</div>

</body>
</html>
