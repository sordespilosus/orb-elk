import { RouterProvider } from 'react-router-dom';
import './App.css';
import router from '../routes/router';
import { useGetCsrfTokenQuery } from '@/api/auth';
import { Loader } from '@/components/ui/Loader';

function App() {
  const { isLoading, data } = useGetCsrfTokenQuery();

  if (isLoading) return <Loader />;

  if (!data) return <div>CSRF token not found</div>;
  return <RouterProvider router={router} />;
}

export default App;
