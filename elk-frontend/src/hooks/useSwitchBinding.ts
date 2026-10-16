import { useEffect } from 'react';
import { FieldValues, Path, UseFormSetValue, UseFormWatch } from 'react-hook-form';

export interface ISwitchBindings<T extends FieldValues> {
  binding: Path<T>;
  parent: Path<T>;
  triggerOn: boolean;
}

interface UseSwitchBindingArgs<T extends FieldValues> {
  switchBindings: ISwitchBindings<T>;
  watch: UseFormWatch<T>;
  setValue: UseFormSetValue<T>;
}

export const useSwitchBinding = <T extends FieldValues>({
  switchBindings,
  watch,
  setValue,
}: UseSwitchBindingArgs<T>) => {
  const bindingSubscription = watch(switchBindings.binding);
  const parentSubscription = watch(switchBindings.parent);

  useEffect(() => {
    if (bindingSubscription === !switchBindings.triggerOn) return;
    setValue(switchBindings.parent, bindingSubscription);
  }, [bindingSubscription]);

  useEffect(() => {
    if (parentSubscription === switchBindings.triggerOn) return;
    setValue(switchBindings.binding, parentSubscription);
  }, [parentSubscription]);
};

// import { useEffect } from 'react';
// import { FieldValues, Path, UseFormSetValue, UseFormWatch } from 'react-hook-form';

//
// interface UseSwitchBindingArgs<T extends FieldValues> {
//   switchBindings: ISwitchBindings<T>[];
//   watch: UseFormWatch<T>;
//   setValue: UseFormSetValue<T>;
// }
//
// export const useSwitchBinding = <T extends FieldValues>({
//                                                           watch,
//                                                           setValue,
//                                                           switchBindings,
//                                                         }: UseSwitchBindingArgs<T>) => {
//   const subscription = watch();
//   useEffect(() => {
//     switchBindings.forEach(({ binding, parent, triggerOn }) => {
//       if (subscription[binding] === triggerOn) {
//         setValue(parent, subscription[binding]);
//         console.log(binding);
//       }
//     });
//   }, [subscription, setValue]);
// };
