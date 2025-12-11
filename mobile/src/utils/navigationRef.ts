/**
 * Navigation Reference Utility
 * 
 * Provides a global navigation reference for use outside of React components
 * (e.g., in API interceptors, event handlers)
 * 
 * @see /home/ktr/mtdev/definitions/mobile/NavigationFlow.md - Section 8.1
 */
import { createNavigationContainerRef } from '@react-navigation/native';

export const navigationRef = createNavigationContainerRef();

/**
 * Navigate to a screen from outside React components
 * 
 * @param name - Screen name
 * @param params - Navigation parameters
 */
export function navigate(name: string, params?: any) {
  if (navigationRef.isReady()) {
    // @ts-ignore - Type assertion for dynamic navigation
    navigationRef.navigate(name, params);
  }
}

/**
 * Reset navigation stack to a specific screen
 * 
 * @param name - Screen name
 * @param params - Navigation parameters
 */
export function resetTo(name: string, params?: any) {
  if (navigationRef.isReady()) {
    navigationRef.reset({
      index: 0,
      routes: [{ name: name as never, params }],
    });
  }
}
