import React, { createContext, useContext, useState } from "react";

const ThemeContext = createContext({ theme: "light", setTheme: () => {} });

export const ThemeProvider = ({ children }) => {
  const [theme, setTheme] = useState("light");
  // Simple provider; extend as needed
  return (
    <ThemeContext.Provider value={{ theme, setTheme }}>
      {children}
    </ThemeContext.Provider>
  );
};

export const useTheme = () => useContext(ThemeContext);

export default ThemeContext;
