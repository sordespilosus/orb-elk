import { userRole } from '@/types/role';
import React from 'react';
import Next from '@/assets/icon/next.svg?react';
export const user = {
  id: 123441,
  role: userRole.admin,
  userName: 'Вахрушев Илья',
  userFullName: 'Вахрушев Илья Максимович',
  avatarUrl: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSCp1EkrKNVQc_QLKjJzZOuq5JbCjKvCCD3vw&s',
};

export const userData = [
  {
    name: 'Населённый пункт',
    value: 'Оренбург',
    onClick: () => {}, //При нажатии открывается модальное окно
    icon: React.createElement(Next),
  },
  {
    name: 'Дата рождения',
    value: '26.06.2001',
  },
  {
    name: 'Пол',
    value: 'Мужской',
  },
  {
    name: 'E-mail',
    value: 'VahrushevIlya@gmail.com',
  },
  {
    name: 'Номер телефона',
    value: '+7 (958) 841-41-21',
  },
  {
    name: 'Номер загранпаспорта',
    value: '73 1234567',
  },
  {
    name: 'ИНН',
    value: '7727563778',
  },
  {
    name: 'Пенсионное свидетельство (СНИЛС)',
    value: '123-456-789 12',
  },
  {
    name: 'Водительское удостоверение',
    value: 'Отсуствует',
    disabled: true,
  },
];
