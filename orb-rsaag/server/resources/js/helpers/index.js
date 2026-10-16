import {format, isValid, parse} from "date-fns";

function getCookie(name) {
  var matches = document.cookie.match(new RegExp(
    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
  ));
  return matches ? decodeURIComponent(matches[1]) : undefined;
};

function setCookie(name, value, options) {
  options = options || {};

  var expires = options.expires;

  if (typeof expires == "number" && expires) {
    var d = new Date();
    d.setTime(d.getTime() + expires * 1000);
    expires = options.expires = d;
  }
  if (expires && expires.toUTCString) {
    options.expires = expires.toUTCString();
  }

  value = encodeURIComponent(value);

  var updatedCookie = name + "=" + value;

  for (var propName in options) {
    updatedCookie += "; " + propName;
    var propValue = options[propName];
    if (propValue !== true) {
      updatedCookie += "=" + propValue;
    }
  }

  document.cookie = updatedCookie;
};

function deleteCookie(name) {
  setCookie(name, "", {
    expires: -1,
    path: '/'
  })
};

function induce(n, titles) {
  return titles[(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)];
};

function maskEmail(email) {
  if(!email) return email;

  const parts = email.split('@'),
      first = parts[0].slice(0, 2),
      last = parts[1] ? parts[1].slice(-2) : '';
  
  return `${first}...@...${last}`;
};

function maskPhone(phone) {
  if(!phone) return phone;
  return `${phone.slice(0,9)} ** *** ${phone.slice(-2)}`;
};

function pluralization(count, texting) {
  const teen = count > 10 && count < 20;
  const endsWithOne = count % 10 === 1 && count % 100 !== 11;

  if (!teen && endsWithOne) {
    return texting[0];
  }

  if (!teen && count % 10 >= 2 && count % 10 <= 4) {
    return texting[1];
  }

  return texting[2];
};

const handlers = {
  emptyHandler: val => (val ? val : 'Не указано'),
  booleanHandler: val => (val ? 'Да' : 'Нет'),
  genderHandler: val => ({male: 'Мужской', female: 'Женский'}[val] || 'Не указано'),
  statusHandler: val => ({ send: 'Отправлено', resend: 'Повторная отправка', wait: 'Ожидание отправки' }[val] || 'Не указано'),
  dateHandler: val => {
    if (!val) return 'Не указано'; //@todo: этот треш надо переписать. начать с адекватных данных на беке.

    const isHoursOmited = val.split(' ').length == 1;
    const parsedDate = parse(
        val,
        isHoursOmited ? 'yyyy-MM-dd' : 'yyyy-MM-dd HH:mm:ss',
        new Date()
    );

    if (!isValid(parsedDate)) return 'Не указано';
    return format(parsedDate, 'dd.MM.yyyy');
  },
  ageHandler: val => {
    const map = {
      1: 'до 18 лет',
      2: '18 - 35 лет',
      3: '35 - 55 лет',
      4: 'более 55 лет'
    };

    return map[val] || 'Не указано';
  }, //@todo: подумать о взаимодействии с store;
}

export {
  handlers,
  getCookie,
  setCookie,
  deleteCookie,
  induce,
  maskEmail,
  maskPhone,
  pluralization
};