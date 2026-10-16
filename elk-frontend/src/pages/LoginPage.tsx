import { FunctionComponent, useCallback, useEffect, useRef } from 'react';
import DigitalEcosystemIcon from '@/assets/logo/DigitalEcosystemLogo.svg?react';
import GosuslugLogo from '@/assets/logo/GosuslugLogo.svg?react';
import LoginButton from '@/components/ui/LoginButton';
import { $url } from '@/utils/config';
import { useSearchParams } from 'react-router-dom';
import { Loader } from '@/components/ui/Loader';
// https://localhost:5173/login?oauth_redirect_uri=http://localhost:5173/oauth/authorize?state=https%3A%2F%2Ftest-upd.orb.ru&scope=personal_data+email&response_type=code&approval_prompt=auto&redirect_uri=https%3A%2F%2Ftest-upd.orb.ru%2Fsite%2Foauth2orenburglogin&client_id=14
const LoginPage: FunctionComponent = () => {
  const params = useSearchParams();
  const searchParams = params[0];
  const isAutoEsia = searchParams.get('auto_esia') === '1';
  const autoEsiaStarted = useRef(false);

  const handleLogin = useCallback(async () => {
    console.log('LOGIN PAGE START');
    console.log('ДЕЛАЕМ ЗАПРОС НА LOGIN ESIA ИЗ ЛОГИН ПАГЕ');

    const oauth_redirect_uri = searchParams.get('oauth_redirect_uri')?.split('?')[0] || '';
    const oauth_redirect_uri_first_param = searchParams.get('oauth_redirect_uri')?.split('?')[1] || '';

    console.log('OAUTH REDIRECT URI', oauth_redirect_uri);
    console.log('OAUTH REDIRECT URI FIRST PARAM', oauth_redirect_uri_first_param);

    let oauthRedirectUri = oauth_redirect_uri + '?' + oauth_redirect_uri_first_param;

    if (searchParams.get('state')) {
      oauthRedirectUri += '&state2=' + searchParams.get('state');
    }
    if (searchParams.get('client_id')) {
      oauthRedirectUri += '&client_id=' + searchParams.get('client_id');
    }
    if (searchParams.get('approval_prompt')) {
      oauthRedirectUri += '&approval_prompt=' + searchParams.get('approval_prompt');
    }
    if (searchParams.get('response_type')) {
      oauthRedirectUri += '&response_type=' + searchParams.get('response_type');
    }
    if (searchParams.get('redirect_uri')) {
      oauthRedirectUri += '&redirect_uri=' + searchParams.get('redirect_uri');
    }
    if (searchParams.get('scope')) {
      oauthRedirectUri += '&scope=' + searchParams.get('scope')?.split(' ').join('+');
    }

    const reqUri =
      `${$url}/api/v2/loginESIA?redirect=` +
      window.location.origin +
      '/fromESIA' +
      '?oauth_redirect_uri=' +
      encodeURIComponent(oauthRedirectUri);

    console.log(reqUri);
    console.log('ЗАПРОС ДЕЛАЕМ НА ЭТОТ УРЛ ВЫШЕ');
    const response = await fetch(reqUri, {
      method: 'GET',
    });

    const data = await response.json();
    console.log('ДАННЫЕ ИЗ ЗАПРОСА', data);
    const redirectUrl = data.redirect as string;
    console.log('РЕДИРЕКТ УРЛ', redirectUrl);
    console.log('LOGIN PAGE END');
    window.location.href = redirectUrl;
  }, [searchParams]);

  useEffect(() => {
    if (!isAutoEsia || autoEsiaStarted.current) {
      return;
    }

    autoEsiaStarted.current = true;
    handleLogin();
  }, [handleLogin, isAutoEsia]);

  if (isAutoEsia) {
    return <Loader />;
  }

  return (
    <main className='grid items-center h-screen px-4'>
      <div className='max-w-[916px] w-full mx-auto bg-card rounded-4xl p-4 grid gap-8 sm:grid-cols-[auto_minmax(0,466px)]'>
        <DigitalEcosystemIcon className='m-auto' />
        <LoginButton
          onClick={handleLogin}
          icon={<GosuslugLogo />}
          title='Войти через Госуслуги'
          description='Даже если вы здесь впервые — вы сможете воспользоваться нашей системой с помощью своего аккаунта Госуслуг'
        />
      </div>
    </main>
  );
};

export default LoginPage;
