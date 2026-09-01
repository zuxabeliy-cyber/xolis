# PAYNET XOLIS — Android ilova (WebView)

Bu — saytni telefonda ilova kabi ochadigan Android loyihasi. Do'kon (Play Market) shart emas, Mac ham kerak emas. APK fayl **GitHub Actions** orqali avtomatik yasaladi.

## 1. Sayt manzilini kiriting
`app/src/main/res/values/strings.xml` faylida `app_url` ni o'z saytingizga o'zgartiring:

```xml
<string name="app_url">https://SIZNING-SAYTINGIZ.ru</string>
```

Ilova nomini ham shu yerda (`app_name`) o'zgartirsa bo'ladi.

## 2. APK ni olish (eng oson — GitHub Actions)
1. Ushbu loyiha GitHub'ga yuklangan (repo: `zuxabeliy-cyber/xolis`).
2. GitHub'da reponi oching → yuqoridagi **Actions** bo'limi.
3. **"Android APK build"** workflow'ini oching → **Run workflow** (yoki `android/` ichida biror o'zgarish push qilinsa o'zi ishga tushadi).
4. Build tugagach (~3-5 daqiqa), pastdagi **Artifacts** bo'limidan **`xolis-app-apk`** ni yuklab oling.
5. Ichidagi `app-debug.apk` ni Android telefonga ko'chirib o'rnating.

> Telefonда "Noma'lum manbalardan o'rnatish" (Install unknown apps) ruxsatini yoqing.

## 3. Kompyuterda build qilish (ixtiyoriy)
Android Studio bilan `android/` papkasini oching → Run. Yoki terminalda:

```bash
cd android
gradle assembleDebug   # yoki: ./gradlew assembleDebug (wrapper bo'lsa)
```

APK: `android/app/build/outputs/apk/debug/app-debug.apk`

## Nималар ishlaydi
- Butun sayt ilova ichida ochiladi (login, baraban, hisobotlar...).
- Fayl tanlash (logo yuklash, CSV import) — ishlaydi.
- Excel / backup yuklab olish — telefon "Downloads" jildiga tushadi.
- Telegram/tel havolalar tashqi ilovada ochiladi.
- "Orqaga" tugmasi sahifalar bo'ylab qaytaradi.

## iPhone uchun
iPhone'da do'konsiz shaxsiy ilova o'rnatish Apple tomonidan cheklangan.
Eng qulay yo'l — **PWA**: Safari'da saytni oching → Ulashish (⬆️) → **"Ekranga qo'shish"**.
Ilova kabi ikonka bilan, to'liq ekranda ochiladi.
