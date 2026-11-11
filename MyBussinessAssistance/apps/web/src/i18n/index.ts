import i18n from "i18next";
import { initReactI18next } from "react-i18next";
import { en } from "./locales/en";
import { km } from "./locales/km";

const STORAGE_KEY = "mba.locale";

function initialLanguage() {
  if (typeof window === "undefined") return "en";
  const saved = window.localStorage.getItem(STORAGE_KEY);
  return saved === "km" || saved === "en" ? saved : "en";
}

void i18n.use(initReactI18next).init({
  resources: {
    en: { translation: en },
    km: { translation: km },
  },
  lng: initialLanguage(),
  fallbackLng: "en",
  interpolation: { escapeValue: false },
  returnNull: false,
});

i18n.on("languageChanged", (language) => {
  if (typeof window !== "undefined") {
    window.localStorage.setItem(STORAGE_KEY, language);
    document.documentElement.lang = language;
  }
});

if (typeof document !== "undefined") {
  document.documentElement.lang = i18n.language;
}

export default i18n;
