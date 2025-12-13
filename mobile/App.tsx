import { GestureHandlerRootView } from 'react-native-gesture-handler';
import AppNavigator from './src/navigation/AppNavigator';
import { ColorSchemeProvider } from './src/contexts/ColorSchemeContext';
import { ThemeProvider } from './src/contexts/ThemeContext';
import { AuthProvider } from './src/contexts/AuthContext';
import { AvatarProvider } from './src/contexts/AvatarContext';
import { FCMProvider } from './src/contexts/FCMContext';

export default function App() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <ColorSchemeProvider>
        <AuthProvider>
          <FCMProvider>
            <ThemeProvider>
              <AvatarProvider>
                <AppNavigator />
              </AvatarProvider>
            </ThemeProvider>
          </FCMProvider>
        </AuthProvider>
      </ColorSchemeProvider>
    </GestureHandlerRootView>
  );
}
