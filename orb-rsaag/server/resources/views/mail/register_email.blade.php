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
        <div style="margin: 30px 0 0 0;">
            <table cellspacing="0" cellpadding="0">
                <tr>
                    <td width="38" style="width:38px;">
                        <img width="30" height="30"
                             src="https://cabinet.permkrai.ru/images/rs2.png"
                             alt="Логотип ГИС ЕЛК ОО" style="border: none; background-repeat: no-repeat; background-size: cover; background-position: center bottom;" border="0">
                    </td>
                    <td>
                        <div style="font-size: 1.2em; color: #000000; line-height: 1.2em;">
                            Единый личный кабинет Оренбургской области
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <hr style="border-top: 1.5px solid rgba(0,0,0,.1)" size="2" color="#455566" width="100%" />

        <div style="margin: 20px 0">
            Уважаемый пользователь!
        </div>

        @if(isset($clientName))
            <div style="margin: 20px 0; line-height: 26px;">
                Вы выполнили вход в систему «{{$clientName}}»  через Единый личный кабинет Оренбургской области (<a href="{{ url('/') }}">ГИС ЕЛК*</a>) @if (isset($esiaLogin)) с помощью Госуслуг (ЕСИА)@endif.
            </div>
        @else
            <div style="margin: 20px 0; line-height: 26px;">
                Добро пожаловать в Единый личный кабинет Оренбургской области - <a href="{{ url('/') }}">ГИС ЕЛК</a>!
            </div>
        @endif


        @if(isset($userLogin, $userPassword))
            <div style="margin: 20px 0; line-height: 26px;">
                Данные для входа в ваш <a href="{{ url('/login') }}">профиль ГИС ЕЛК</a>:<br>
                <b>Ваш логин: </b>{{$userLogin}}<br>
                <b>Ваш пароль: </b>{{$userPassword}}<br>

                <div style="margin: 20px 0; line-height: 26px;">
                    @if(isset($clientName))
                        С помощью ГИС ЕЛК вам доступен вход во
                    @else
                        Теперь вам доступны
                    @endif
                        все официальные ресурсы Оренбургской области!
                </div>

                Вы сможете изменить информацию о себе в <a href="{{ url('/profile') }}">профиле ЕЛК***</a>!
            </div>
        @endif

        @if(isset($clientName, $clientDescription))
            <div style="margin: 20px 0; line-height: 26px;">
                {{$clientName}} - {{$clientDescription}}.
            </div>
        @endif


        <div style="margin: 20px 0; line-height: 26px;">
            Участвуйте в развитии Оренбургской области!
        </div>

        <hr size="1" color="#e6e6e6" style="height:1px;" />

        <div style="margin:0px 0 0px 0; padding: 10px 0 0 0; line-height: 26px;">
            С уважением, <br>
            Команда Единого личного кабинета Оренбургской области <a href="{{ url('/') }}">«ГИС ЕЛК»</a>
        </div>

        @if($clientName)
            <div style="margin:20px 0; color: #7e888c; line-height: 26px;">
                * ЕЛК – Единый личный кабинет Оренбургской Области
            </div>
        @endif

        <div style="margin:20px 0; color: #7e888c; line-height: 26px;">
            Пожалуйста, не отвечайте на это письмо, так как оно сгенерировано автоматически.
        </div>

    </div>
</div>

</body>
</html>
