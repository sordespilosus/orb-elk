import { ChangeEvent, FunctionComponent, useEffect, useState } from 'react';
import { Input } from '../ui-shadcn/input';
import { Button } from '../ui-shadcn/button';
import Search from '@/assets/icon/search.svg?react';
import { CityResponce, useCitiesQuery } from '@/api/citises';
import { useDebounceValue } from '@/utils/useDebounceValue';

interface EditCityProps {
  onChange: (value: number) => void;
}

const EditCity: FunctionComponent<EditCityProps> = ({ onChange }) => {
  const [q, setQ] = useState('');
  const debouncedQ = useDebounceValue(q, 450);
  const [currentCities, setCurrentCities] = useState<CityResponce | null>(null);
  const { data: cities } = useCitiesQuery({ q: debouncedQ, count: 7 });
  const [active, setActive] = useState(0);

  useEffect(() => {
    if (!cities) return;
    setCurrentCities(cities);
  }, [cities]);

  const onChangeCity = (id: number) => {
    onChange(id);
    setActive(id);
  };
  const onChangeInput = (e: ChangeEvent<HTMLInputElement>) => {
    setQ(e.target.value);
  };
  return (
    <div>
      <div className='mb-4'>
        <Input value={q} autoFocus leftIcon={<Search />} placeholder='Введите Город' onChange={onChangeInput}></Input>
      </div>
      <div>
        {!currentCities && <div>Loading...</div>}
        {currentCities?.data.length == 0 && <div>Not found</div>}
        {currentCities?.data.map((city, index) => (
          <Button
            key={index}
            className='text-left block w-full'
            variant={active === city.area_id ? 'default' : 'outline'}
            onClick={() => onChangeCity(city.area_id)}
          >
            {city.area_name}
          </Button>
        ))}
      </div>
    </div>
  );
};

export default EditCity;
