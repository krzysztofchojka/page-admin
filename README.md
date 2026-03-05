# page-admin 2.0

Lekki, autorski system CMS oparty na architekturze **MVC**.  
Posiada wbudowany **Page Builder (Drag & Drop)**, szyfrowany system formularzy oraz własny system mailowy.

Filozofią tego projektu jest **Drop & Run** – instalacja na serwerze produkcyjnym **nie wymaga terminala, Node.js ani skomplikowanej konfiguracji**.  
Wgrywasz pliki przez FTP i strona działa.

## Instalacja na serwerze klienta (Produkcja)

Aby uruchomić system na dowolnym hostingu (np. Apache, Litespeed):

### 1. Pobierz projekt

Pobierz pliki projektu jako **ZIP** lub wykonaj clone z GitHuba.

### 2. Wgraj pliki na serwer

Wrzuć pliki na serwer przez FTP.

Katalog `public` musi być **głównym katalogiem domeny**.

Można to osiągnąć przez:

- plik `.htaccess` w głównym katalogu
- odpowiednią konfigurację **vhosta**

### 3. Utwórz bazę danych

W panelu hostingowym utwórz bazę **MySQL / MariaDB**.

### 4. Skonfiguruj plik `.env`

Zmień nazwę pliku:

```
.env.example
```

na:

```
.env
```

Następnie wpisz dane swojej bazy danych:

```ini
DB_HOST=localhost
DB_NAME=nazwa_bazy
DB_USER=uzytkownik
DB_PASS=haslo
APP_ENV=production
```

### 5. Uruchom instalator

Wejdź w przeglądarce pod adres:

```
twojadomena.pl/install
```

Skrypt wygeneruje strukturę tabel w bazie danych.

### 6. Logowanie

Panel administracyjny:

```
twojadomena.pl/login
```

Dane domyślne:

```
Login: admin
Hasło: admin
```

System **natychmiast wymusi zmianę hasła** po pierwszym logowaniu.

## Wymagania serwera

- PHP **8.1+**
- **MySQL / MariaDB**
- Apache / LiteSpeed / Nginx

## Przewodnik dla programistów (Środowisko Dev)

Projekt wykorzystuje **system komponentów** do budowy bloków oraz **Tailwind CSS**.

Pliki CSS są generowane przy pomocy **Node.js**, ale **Node nigdy nie trafia na serwer klienta**.

---

## Inicjalizacja projektu

Otwórz terminal w folderze projektu i zainstaluj zależności frontendowe (tylko raz):

```bash
npm install
```

---

## 2. Development

Podczas pracy nad projektem uruchom **dwa procesy w terminalu**.

---

## Terminal 1 — Kompilator CSS (Tailwind)

Uruchom watcher, który będzie śledził zmiany w plikach:

```bash
npm run dev
```

---

## Terminal 2 — Serwer PHP

Uruchom wbudowany serwer PHP:

```bash
php -S localhost:8000 -t public
```

Następnie otwórz w przeglądarce:

```
http://localhost:8000
```

---

# Wysyłka na produkcję (Kluczowy proces)

Zanim wykonasz **git push** lub wgrasz pliki na serwer klienta, musisz skompilować zoptymalizowany CSS:

```bash
npm run build
```

Komenda:

- kompresuje styl
- generuje plik:

```
public/assets/css/style.css
```

Plik ma zazwyczaj **kilka kilobajtów**.

Na serwer produkcyjny wysyłasz tylko:

- kod **PHP**
- wygenerowany plik **style.css**

Folder:

```
node_modules
```

**zostaje tylko na Twoim komputerze**.

---

## Architektura klocków (Page Builder)

Wszystkie moduły strony (**klocki**) znajdują się w folderze:

```
src/Blocks/
```

Każdy blok implementuje:

```
BlockInterface
```

## Dodawanie nowego klocka

## 1. Stwórz plik

```
src/Blocks/MyNewBlock.php
```

## 2. Zaimplementuj metodę

```
render()
```

## 3. Zarejestruj blok

Dodaj go do tablicy w pliku:

```
src/Helpers/BlockRenderer.php
```

## Główne cechy

- architektura **MVC**
- **Drop & Run deployment**
- **Page Builder Drag & Drop**
- szyfrowane formularze
- obsługa **RODO**
- własny system mailowy
- **Tailwind CSS**
- brak zależności Node na produkcji
