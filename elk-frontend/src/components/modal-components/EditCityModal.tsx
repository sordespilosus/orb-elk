import { FunctionComponent, useContext, useEffect, useState } from 'react';
import Modal from '../ui/Modal';
import EditCity from '../profile/EditCity';
import { usePatchProfileMutation } from '@/api/user';
import { ToastContext } from '../toast/ToastContext';

interface EditCityModalProps {
  isOpen: boolean;
  onClose: () => void;
}

const EditCityModal: FunctionComponent<EditCityModalProps> = ({ isOpen, onClose }) => {
  const [idCity, setIdCity] = useState<number>();
  const { mutate: updateUser, isSuccess, isError } = usePatchProfileMutation();
  const { toast } = useContext(ToastContext);

  const onSubmit = () => {
    if (!idCity) {
      toast({ message: 'Выберите город', title: 'Ошибка', variant: 'error' });
      return;
    }
    updateUser({ city: idCity.toString() });
  };

  useEffect(() => {
    if (isSuccess) {
      toast({ message: 'Город успешно изменен', title: 'Сохранено' });
      onClose();
    }
  }, [isSuccess]);

  useEffect(() => {
    if (isError) {
      toast({ message: 'Ошибка при изменении города', title: 'Ошибка', variant: 'error' });
      onClose();
    }
  }, [isError]);

  return (
    <Modal
      open={isOpen}
      onClose={onClose}
      onSubmit={onSubmit}
      header='Выбор города'
      main={<EditCity onChange={(value) => setIdCity(value)} />}
    />
  );
};

export default EditCityModal;
