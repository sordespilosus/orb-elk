import React, { FunctionComponent, memo, useState } from 'react';
import CardProfile from '../ui/Card';
import ItemProfileInfo from './ItemProfileInfo';
import EditCityModal from '../modal-components/EditCityModal';
import { useUserProfileQuery } from '@/api/user';
import Next from '@/assets/icon/next.svg?react';

const UserData: FunctionComponent = () => {
  const { data: profileData } = useUserProfileQuery();

  const [isModalOpen, setIsModalOpen] = useState(false);

  const handleOpenModal = () => {
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
  };

  return (
    <>
      <CardProfile className='px-8 py-5 mobile:col-span-2 md:col-span-1'>
        <div>
          <ItemProfileInfo
            name={'Населённый пункт'}
            value={profileData?.area_name || 'Отсутствует'}
            icon={React.createElement(Next)}
            onClick={handleOpenModal}
            disabled={!profileData?.area_name}
          />
          <ItemProfileInfo
            name={'Дата рождения'}
            value={profileData?.birthday || 'Отсутствует'}
            disabled={!profileData?.birthday}
          />
          <ItemProfileInfo
            name={'Пол'}
            value={profileData?.gender ? (profileData?.gender === 'male' ? 'Мужской' : 'Женский') : 'Отсутствует'}
            disabled={!profileData?.gender}
          />
          <ItemProfileInfo name={'E-mail'} value={profileData?.email || 'Отсутствует'} disabled={!profileData?.email} />
          <ItemProfileInfo
            name={'Номер телефона'}
            value={profileData?.phone || 'Отсутствует'}
            disabled={!profileData?.phone}
          />
        </div>
        <div className='mobile:hidden'>
          <ItemProfileInfo
            name={'Номер загранпаспорта'}
            value={profileData?.international_passport || 'Отсутствует'}
            disabled={!profileData?.international_passport}
          />
          <ItemProfileInfo name={'ИНН'} value={profileData?.inn || 'Отсутствует'} disabled={!profileData?.inn} />
          <ItemProfileInfo
            name={'Пенсионное свидетельство (СНИЛС)'}
            value={profileData?.pension_certificate || 'Отсутствует'}
            disabled={!profileData?.pension_certificate}
          />
          <ItemProfileInfo
            name={'Водительское удостоверение'}
            value={profileData?.driver_license || 'Отсутствует'}
            disabled={!profileData?.driver_license}
          />
        </div>
        <EditCityModal isOpen={isModalOpen} onClose={handleCloseModal} />
      </CardProfile>
      <CardProfile className='px-8 py-5 max-mobile:hidden'>
        <div className=''>
          <ItemProfileInfo
            name={'Номер загранпаспорта'}
            value={profileData?.international_passport || 'Отсутствует'}
            disabled={!profileData?.international_passport}
          />
          <ItemProfileInfo name={'ИНН'} value={profileData?.inn || 'Отсутствует'} disabled={!profileData?.inn} />
          <ItemProfileInfo
            name={'Пенсионное свидетельство (СНИЛС)'}
            value={profileData?.pension_certificate || 'Отсутствует'}
            disabled={!profileData?.pension_certificate}
          />
          <ItemProfileInfo
            name={'Водительское удостоверение'}
            value={profileData?.driver_license || 'Отсутствует'}
            disabled={!profileData?.driver_license}
          />
        </div>
      </CardProfile>
    </>
  );
};

export default memo(UserData);
