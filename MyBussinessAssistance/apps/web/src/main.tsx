import React from "react";
import ReactDOM from "react-dom/client";
import { BrowserRouter } from "react-router-dom";
import App from "./App";
import "./index.css";
import "./i18n";
import { ThemeProvider } from "./providers/theme-provider";
import { ErrorBoundary } from "./components/error-boundary";
import { QueryProvider } from "./providers/query-provider";
import { AuthProvider } from "./providers/auth-provider";

ReactDOM.createRoot(document.getElementById("root") as HTMLElement).render(
  <React.StrictMode>
    <ThemeProvider>
      <QueryProvider>
        <AuthProvider>
          <ErrorBoundary>
            <BrowserRouter
              future={{ v7_startTransition: true, v7_relativeSplatPath: true }}
            >
              <App />
            </BrowserRouter>
          </ErrorBoundary>
        </AuthProvider>
      </QueryProvider>
    </ThemeProvider>
  </React.StrictMode>,
);
