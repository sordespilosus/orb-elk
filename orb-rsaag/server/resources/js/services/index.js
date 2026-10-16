import axios from 'axios';

const { headers } = axios.defaults;
const token = document.getElementById('csrf');

if(token) {
  headers.common['X-CSRF-TOKEN'] = token.content;
} else {
  console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

headers.common['X-Requested-With'] = 'XMLHttpRequest';

export default axios.create();