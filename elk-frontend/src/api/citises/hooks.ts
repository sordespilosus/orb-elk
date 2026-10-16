import { useQuery } from '@tanstack/react-query'
import { citiesApi } from './api'

export const useCitiesQuery = ({ q = '', count = 7 }: { q?: string, count?: number }) => {
    return useQuery({
        queryKey: ['cities', q, count],
        queryFn: () => citiesApi.getCities({ q, count }),
    })
}