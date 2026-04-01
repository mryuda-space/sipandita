# SIPANDITA - Dashboard Analisis Berita

Dashboard monitoring sentimen berita berbasis PHP + MySQL.

## Cara Install
1. Clone repository ini
2. Copy `koneksi.example.php` dan rename menjadi `koneksi.php`
3. Sesuaikan isi `koneksi.php` dengan database kamu
4. Import file `db_sipandita.sql` ke phpMyAdmin
5. Download wordcloud2.js dan taruh di folder ini
6. Buka `localhost/sipandita` di browser

## Teknologi
- PHP
- MySQL
- Chart.js
- Bootstrap 5
- Wordcloud2.js

## Fitur
- Stat card Total, Positive, Negative, Neutral
- Bar chart perbandingan tone
- Pie chart persentase
- Line chart tren per hari (7, 30, 90 hari, 1 tahun)
- Wordcloud keyword per tone
- Top 10 media terbanyak
- Filter periode waktu
- Perbandingan periode