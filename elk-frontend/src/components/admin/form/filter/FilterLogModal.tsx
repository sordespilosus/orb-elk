import { FC, useEffect } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { filterLogSchema, LogSchemaType } from '@/components/admin/form/filter/schemas/log.schema.ts';
import { zodResolver } from '@hookform/resolvers/zod';
import Modal from '@/components/ui/Modal.tsx';
import { UiControlledFormInput } from '@/components/ui/UiControlledFormInput.tsx';
import { FetchLogsParams } from '@/api/admin';
import { formatISO, parse } from 'date-fns';

interface FilterLogModalProps {
  open: boolean;
  onClose: () => void;
  onSubmitData?: (submitData: FetchLogsParams) => void;
}

const FilterLogModal: FC<FilterLogModalProps> = ({ open, onClose, onSubmitData }) => {
  const methods = useForm<LogSchemaType>({
    resolver: zodResolver(filterLogSchema),
    mode: 'onChange',
  });
  // const { isValid } = methods.formState;
  useEffect(() => {
    return () => {
      methods.reset();
    };
  }, []);

  console.log(methods.formState.errors);

  const transformFormData = (formData: LogSchemaType): FetchLogsParams => {
    const { from, to } = formData.dateRange;
    const minDate = parse(formData.timeLeftRange ?? '00:00', 'HH:mm', from);
    const maxDate = parse(formData.timeRightRange ?? '23:59', 'HH:mm', to ?? from);
    console.log(minDate, maxDate);
    return {
      min_date: formatISO(minDate),
      max_date: formatISO(maxDate),
    };
  };

  const onSubmit = (formData: LogSchemaType) => {
    const transformedData = transformFormData(formData);
    onSubmitData?.(transformedData);
    console.log(formData);
    console.log(transformedData);
    onClose();
  };
  return (
    <FormProvider {...methods}>
      <Modal
        open={open}
        onClose={onClose}
        header='Фильтры'
        actionBtnText='Применить'
        main={
          <div className={''}>
            <UiControlledFormInput
              input={{
                formName: 'dateRange',
                type: 'dateRangePicker',
                placeholder: 'ДД.ММ.ГГ - ДД.ММ.ГГ',
                required: true,
                label: 'Период',
              }}
              control={methods.control}
              errors={methods.formState.errors}
            />
            <div className={`flex items-center justify-items-stretch mt-3 gap-[10px]`}>
              <div className={'grow'}>
                <UiControlledFormInput
                  input={{
                    formName: 'timeLeftRange',
                    type: 'input',
                    inputType: 'time',
                    placeholder: 'ЧЧ:ММ',
                    required: false,
                  }}
                  control={methods.control}
                  errors={methods.formState.errors}
                />
              </div>

              <span>-</span>
              <div className={'grow'}>
                <UiControlledFormInput
                  input={{
                    formName: 'timeRightRange',
                    type: 'input',
                    inputType: 'time',
                    placeholder: 'ЧЧ:ММ',
                    required: false,
                  }}
                  control={methods.control}
                  errors={methods.formState.errors}
                />
              </div>
            </div>
          </div>
        }
        onSubmit={methods.handleSubmit(onSubmit)}
      />
    </FormProvider>
  );
};

export default FilterLogModal;
