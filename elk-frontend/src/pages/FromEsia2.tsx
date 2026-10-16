import { Loader } from '@/components/ui/Loader';
import { $url } from '@/utils/config';
import { useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';

const fetchToEsia = async (params: URLSearchParams) => {
  console.log('FETCH TO ESIA START');
  console.log('ДЕЛАЕМ ЗАПРОС СЮДА', `${$url}/api/v2/loginESIA?` + params.toString());
  const response = await fetch(`${$url}/api/v2/loginESIA?` + params.toString(), {
    method: 'GET',
    credentials: 'include',
  });
  const data = await response.json();
  console.log('ДАННЫЕ ИЗ ЗАПРОСА', data);
  return data;
};

export const FromESIA2 = () => {
  const params = useSearchParams();
  const navigate = useNavigate();

  useEffect(() => {
    console.log('FROM ESIA2 PAGE START');
    console.log('ДЕЛАЕМ ЗАПРОС НА LOGIN ESIA ИЗ FROM ESIA2');
    console.log('ПАРАМЕТРЫ ТЕКУЩИЕ В УРЛЕ', params[0].toString());
    fetchToEsia(params[0]).then((e) => {
      console.log('ДАННЫЕ ИЗ ЗАПРОСА FROM ESIA2', e);
      if (e.redirect) {
        const oauthRedirectUri = params[0].get('oauth_redirect_uri');
        console.log('OAUTH REDIRECT URI', oauthRedirectUri);

        const paramsObject = Object.fromEntries(params[0]);
        console.log('PARAMS OBJECT', paramsObject);

        const addParams = new URLSearchParams();
        if (params[0].get('redirect_uri')) addParams.set('redirect_uri', params[0].get('redirect_uri') || '');
        if (params[0].get('response_type')) addParams.set('response_type', params[0].get('response_type') || '');
        if (params[0].get('scope')) addParams.set('scope', params[0].get('scope')?.split(' ').join(' ') || '');
        // if (params[0].getAll('state').length > 0) {
        //   const validState = params[0].getAll('state').find((s) => {
        //     if (s === 'mobile') {
        //       return true;
        //     }
        //     try {
        //       new URL(s);
        //       return true;
        //     } catch {
        //       return false;
        //     }
        //   });
        //   if (validState) {
        //     addParams.set('state', validState);
        //   }
        // }
        if (params[0].get('state2')) addParams.set('state', params[0].get('state2') || '');
        if (params[0].get('client_id')) addParams.set('client_id', params[0].get('client_id') || '');
        if (params[0].get('approval_prompt')) addParams.set('approval_prompt', params[0].get('approval_prompt') || '');

        if (oauthRedirectUri && oauthRedirectUri !== 'null') {
          const oauth_redirect_uri = oauthRedirectUri?.split('?')[0] || '';
          const oauth_redirect_uri_first_param = oauthRedirectUri?.split('?')[1] || '';

          const redirectUrl = oauth_redirect_uri + '?' + oauth_redirect_uri_first_param + '&' + addParams.toString();
          console.log('ПЕРЕХОДИМ НА РЕДИРЕКТ УРЛ', redirectUrl);
          console.log("FROM ESIA2 PAGE END (ПОПАЛИ В if(oauthRedirectUri && oauthRedirectUri !== 'null'))");
          window.location.href = redirectUrl;
          return;
        }
        console.log('ПЕРЕХОДИМ НА ГЛАВНУЮ СТРАНИЦУ', '/');
        console.log('FROM ESIA2 PAGE END (ПОПАЛИ В else)');
        navigate('/');
      }
    });
  }, []);

  return <Loader />;
};
