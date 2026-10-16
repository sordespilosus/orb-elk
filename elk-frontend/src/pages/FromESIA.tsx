import { Loader } from '@/components/ui/Loader';
import { $url } from '@/utils/config';
import { useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';

const fetchToEsia = async (params: URLSearchParams) => {
  console.log('FETCH TO ESIA START');

  const oauth_redirect_uri = params.get('oauth_redirect_uri')?.split('?')[0] || '';
  const oauth_redirect_uri_first_param = params.get('oauth_redirect_uri')?.split('?')[1] || '';

  console.log('OAUTH REDIRECT URI', oauth_redirect_uri);
  console.log('OAUTH REDIRECT URI FIRST PARAM', oauth_redirect_uri_first_param);

  let oauthRedirectUri = oauth_redirect_uri + '?' + oauth_redirect_uri_first_param;

  // if (params.getAll('state').length > 0) {
  //   const validState = params.getAll('state').find((s) => {
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
  //     oauthRedirectUri += '&state=' + validState;
  //   }
  // }

  if (params.get('state2')) {
    oauthRedirectUri += '&state2=' + params.get('state2');
  }

  if (params.get('client_id')) {
    oauthRedirectUri += '&client_id=' + params.get('client_id');
  }
  if (params.get('approval_prompt')) {
    oauthRedirectUri += '&approval_prompt=' + params.get('approval_prompt');
  }
  if (params.get('response_type')) {
    oauthRedirectUri += '&response_type=' + params.get('response_type');
  }
  if (params.get('redirect_uri')) {
    oauthRedirectUri += '&redirect_uri=' + params.get('redirect_uri');
  }
  if (params.get('scope')) {
    oauthRedirectUri += '&scope=' + params.get('scope')?.split(' ').join('+');
  }

  console.log('OAUTH REDIRECT URI', oauthRedirectUri);

  const reqParams = new URLSearchParams();
  reqParams.set('redirect_uri', params.get('redirect_uri') || '');
  reqParams.set('response_type', params.get('response_type') || '');
  reqParams.set('scope', params.get('scope')?.split(' ').join('+') || '');
  reqParams.set('state', params.getAll('state')[1] || '');
  reqParams.set('processAuthCodeResponse', params.get('processAuthCodeResponse') || '');
  reqParams.set('code', params.get('code') || '');

  console.log('REQUEST PARAMS', reqParams.toString());

  const fetchUrl =
    `${$url}/api/v2/loginESIA?` +
    reqParams.toString() +
    '&redirect=' +
    window.location.origin +
    '/fromEsia2' +
    '?oauth_redirect_uri=' +
    encodeURIComponent(oauthRedirectUri);

  console.log('ДЕЛАЕМ ЗАПРОС СЮДА', fetchUrl);
  const response = await fetch(fetchUrl, {
    method: 'GET',
    credentials: 'include',
    redirect: 'follow',
  });
  const data = await response.json();
  console.log('ДАННЫЕ ИЗ ЗАПРОСА', data);
  return data;
};

export const FromESIA = () => {
  const params = useSearchParams();

  useEffect(() => {
    console.log('FROM ESIA PAGE START');
    console.log('ДЕЛАЕМ ЗАПРОС НА LOGIN ESIA ИЗ FROM ESIA');
    console.log('ПАРАМЕТРЫ ТЕКУЩИЕ В УРЛЕ', params[0].toString());
    fetchToEsia(params[0]).then((e) => {
      console.log('ДАННЫЕ ИЗ ЗАПРОСА FROM ESIA', e);
      if (e.redirect) {
        console.log('РЕДИРЕКТ УРЛ', e.redirect);
        console.log('FROM ESIA PAGE END (ПОПАЛИ В if(e.redirect))');
        window.location.href = e.redirect;
      }
      console.log('FROM ESIA PAGE END (ПОПАЛИ В else)');
    });
  }, []);

  return <Loader />;
};
