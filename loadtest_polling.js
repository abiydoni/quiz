import http from 'k6/http';
import { sleep } from 'k6';

export let options = {
  vus: 100, // 100 virtual users (simulasi 100 peserta)
  duration: '1m', // selama 2 menit
};

export default function () {
  http.get('https://quiz.appsbee.my.id/api/quiz.php?action=status_presentasi&kode=E408FC');
  sleep(2); // polling setiap 2 detik
}