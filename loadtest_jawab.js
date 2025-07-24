import http from 'k6/http';

export let options = {
  vus: 100, // 100 peserta
  duration: '1m',
};

export default function () {
  // Ganti id_peserta dan id_soal sesuai data nyata di database
  let payload = 'id_peserta=1&id_soal=1&jawaban=A';
  let params = { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } };
  // http.post('http://localhost/quiz/api/quiz.php?action=jawab', payload, params);
  http.post('https://quiz.appsbee.my.id/api/quiz.php?action=jawab', payload, params);
}