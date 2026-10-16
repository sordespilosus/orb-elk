import { FC, useCallback, useEffect, useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import {
  BooleanFilterValue,
  filterUserSchema,
  FilterUserSchemaType,
} from '@/components/admin/form/filter/schemas/filter-user.schema.ts';
import { UiControlledFormInput } from '@/components/ui/UiControlledFormInput.tsx';
import { FILTER_USER } from '@/components/admin/form/filter/constants/filterUser.ts';
import { cn } from '@/lib/utils.ts';
import Modal from '@/components/ui/Modal.tsx';
import { GetUsersParams } from '@/api/users';
import { FilterType } from '@/components/admin/form/filter/types/types.ts';
import { useDebounceValue } from '@/utils/useDebounceValue.ts';
import { CityResponce, useCitiesQuery } from '@/api/citises';

interface FilterUserModalProps {
  open: boolean;
  onClose: () => void;
  onSubmitData?: (submitData: GetUsersParams) => void;
}

const getBooleanFilterValue = (value?: BooleanFilterValue): boolean | undefined => {
  if (value === 'true') return true;
  if (value === 'false') return false;

  return undefined;
};

const getGenderFilterValue = (value?: string): GetUsersParams['gender'] => {
  if (value === 'male' || value === 'female') return value;

  return undefined;
};

const FilterUserModal: FC<FilterUserModalProps> = ({ open, onClose, onSubmitData }) => {
  const methods = useForm<FilterUserSchemaType>({
    resolver: zodResolver(filterUserSchema),
    mode: 'onChange',
  });
  const { isValid } = methods.formState;
  const [filterUsers, setFilterUsers] = useState<FilterType[]>(FILTER_USER);
  const [q, setQ] = useState('');
  const debouncedQ = useDebounceValue(q, 450);
  const [currentCities, setCurrentCities] = useState<CityResponce | null>(null);
  const { data: cities } = useCitiesQuery({ q: debouncedQ, count: 7 });

  const onChangeSelectInput = useCallback(async (value: string) => {
    console.log(`Selected value: ${value}`);
    setQ(value);
  }, []);

  useEffect(() => {
    if (!cities) return;
    setCurrentCities(cities);
  }, [cities]);

  useEffect(() => {
    console.log('currentCities');
    if (currentCities) {
      const transformedCities = currentCities.data.map((city) => ({
        name: city.area_name,
        value: city.area_id.toString(),
      }));

      setFilterUsers((prevFilters) =>
        prevFilters.map((filter) =>
          filter.input.formName === 'settlement' ? { ...filter, selectData: transformedCities } : filter
        )
      );
    }
  }, [currentCities]);

  const initValueSelectCities = () => {
    if (open) {
      setFilterUsers((prevFilters) =>
        prevFilters.map((filter) =>
          filter.input.formName === 'settlement'
            ? {
                ...filter,
                input: { ...filter.input, onChangeSelectInput },
              }
            : filter
        )
      );
    }
  };
  useEffect(() => {
    initValueSelectCities();
  }, [open, onChangeSelectInput]);

  const { watch, trigger } = methods;

  useEffect(() => {
    const subscription = watch((_, { name }) => {
      if (name === 'minAge' || name === 'maxAge') {
        trigger(['minAge', 'maxAge']);
      }
    });

    return () => subscription.unsubscribe();
  }, [watch, trigger]);

  const onSubmit = (formData: FilterUserSchemaType) => {
    const transformedData: GetUsersParams = {
      id: formData.id ? Number(formData.id) : undefined,
      first_name: formData.name,
      second_name: formData.surname,
      patroname: formData.patronymic,
      city: formData.settlement,
      age_period_from: formData.minAge,
      age_period_to: formData.maxAge,
      gender: getGenderFilterValue(formData.gender),
      is_admin: getBooleanFilterValue(formData.isAdmin),
      is_blocked: getBooleanFilterValue(formData.isBlocked),
      only_verified: getBooleanFilterValue(formData.isConfirmed),
    };
    onSubmitData?.(transformedData);
    onClose();
  };
  return (
    <FormProvider {...methods}>
      <Modal
        open={open}
        onClose={onClose}
        disabled={!isValid}
        header='Фильтры'
        actionBtnText='Применить'
        main={
          <div className={'grid grid-cols-2'}>
            {filterUsers.map((data, index) => (
              <div key={index} className={`col-span-2 ${data.input.className}`}>
                <UiControlledFormInput
                  control={methods.control}
                  errors={methods.formState.errors}
                  input={data.input}
                  variantFormInput={cn(data.input.variant)}
                  selectData={data.selectData}
                />
              </div>
            ))}
          </div>
        }
        onSubmit={methods.handleSubmit(onSubmit)}
      />
    </FormProvider>
  );
};

export default FilterUserModal;
