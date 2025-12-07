import AppNavigator from './src/navigation/AppNavigator';
import { ThemeProvider } from './src/contexts/ThemeContext';
import { AuthProvider } from './src/contexts/AuthContext';
import { AvatarProvider } from './src/contexts/AvatarContext';

export default function App() {
  return (
    <AuthProvider>
      <ThemeProvider>
        <AvatarProvider>
          <AppNavigator />
        </AvatarProvider>
      </ThemeProvider>
    </AuthProvider>
  );
}
