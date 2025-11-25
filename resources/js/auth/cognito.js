/**
 * Amazon Cognito認証サービス
 * 
 * Phase 1: JWT認証への移行
 * amazon-cognito-identity-jsを使用してCognito User Poolと連携
 * 
 * @see https://github.com/aws-amplify/amplify-js/tree/main/packages/amazon-cognito-identity-js
 */

import {
    CognitoUserPool,
    CognitoUser,
    AuthenticationDetails,
    CognitoUserAttribute,
    CognitoUserSession
} from 'amazon-cognito-identity-js';

// User Pool設定
const poolData = {
    UserPoolId: import.meta.env.VITE_COGNITO_USER_POOL_ID,
    ClientId: import.meta.env.VITE_COGNITO_CLIENT_ID
};

const userPool = new CognitoUserPool(poolData);

/**
 * Cognito認証サービスクラス
 */
export class CognitoAuthService {
    /**
     * ログイン
     * 
     * @param {string} email メールアドレス
     * @param {string} password パスワード
     * @returns {Promise<Object>} トークン情報
     */
    static login(email, password) {
        return new Promise((resolve, reject) => {
            const authenticationData = {
                Username: email,
                Password: password,
            };
            const authenticationDetails = new AuthenticationDetails(authenticationData);

            const userData = {
                Username: email,
                Pool: userPool
            };
            const cognitoUser = new CognitoUser(userData);

            cognitoUser.authenticateUser(authenticationDetails, {
                onSuccess: (session) => {
                    const tokens = {
                        accessToken: session.getAccessToken().getJwtToken(),
                        idToken: session.getIdToken().getJwtToken(),
                        refreshToken: session.getRefreshToken().getToken()
                    };
                    
                    // ローカルストレージに保存
                    this.saveTokens(tokens);
                    
                    resolve(tokens);
                },
                onFailure: (err) => {
                    console.error('Cognito login failed:', err);
                    reject(err);
                },
                // MFAチャレンジ（オプション）
                mfaRequired: (codeDeliveryDetails) => {
                    const verificationCode = prompt('MFA検証コードを入力してください:');
                    if (verificationCode) {
                        cognitoUser.sendMFACode(verificationCode, {
                            onSuccess: (session) => {
                                const tokens = {
                                    accessToken: session.getAccessToken().getJwtToken(),
                                    idToken: session.getIdToken().getJwtToken(),
                                    refreshToken: session.getRefreshToken().getToken()
                                };
                                this.saveTokens(tokens);
                                resolve(tokens);
                            },
                            onFailure: (err) => {
                                reject(err);
                            }
                        });
                    } else {
                        reject(new Error('MFA verification cancelled'));
                    }
                },
                // 新しいパスワード要求（初回ログイン時）
                newPasswordRequired: (userAttributes, requiredAttributes) => {
                    console.log('New password required');
                    // 実装: 新しいパスワード入力画面へ遷移
                    reject(new Error('New password required'));
                }
            });
        });
    }

    /**
     * ユーザー登録
     * 
     * @param {string} email メールアドレス
     * @param {string} password パスワード
     * @param {string} name 名前
     * @param {string} timezone タイムゾーン（オプション）
     * @returns {Promise<Object>} 登録結果
     */
    static register(email, password, name, timezone = 'Asia/Tokyo') {
        return new Promise((resolve, reject) => {
            const attributeList = [
                new CognitoUserAttribute({ Name: 'email', Value: email }),
                new CognitoUserAttribute({ Name: 'name', Value: name }),
                new CognitoUserAttribute({ Name: 'custom:timezone', Value: timezone })
            ];

            userPool.signUp(email, password, attributeList, null, (err, result) => {
                if (err) {
                    console.error('Cognito registration failed:', err);
                    reject(err);
                    return;
                }
                
                console.log('User registered successfully:', result.user.getUsername());
                resolve({
                    user: result.user,
                    userConfirmed: result.userConfirmed,
                    userSub: result.userSub
                });
            });
        });
    }

    /**
     * メール確認（登録後）
     * 
     * @param {string} email メールアドレス
     * @param {string} confirmationCode 確認コード
     * @returns {Promise<string>} 確認結果
     */
    static confirmRegistration(email, confirmationCode) {
        return new Promise((resolve, reject) => {
            const userData = {
                Username: email,
                Pool: userPool
            };
            const cognitoUser = new CognitoUser(userData);

            cognitoUser.confirmRegistration(confirmationCode, true, (err, result) => {
                if (err) {
                    console.error('Confirmation failed:', err);
                    reject(err);
                    return;
                }
                resolve(result);
            });
        });
    }

    /**
     * 確認コード再送信
     * 
     * @param {string} email メールアドレス
     * @returns {Promise<string>} 送信結果
     */
    static resendConfirmationCode(email) {
        return new Promise((resolve, reject) => {
            const userData = {
                Username: email,
                Pool: userPool
            };
            const cognitoUser = new CognitoUser(userData);

            cognitoUser.resendConfirmationCode((err, result) => {
                if (err) {
                    console.error('Resend confirmation code failed:', err);
                    reject(err);
                    return;
                }
                resolve(result);
            });
        });
    }

    /**
     * ログアウト
     */
    static logout() {
        const cognitoUser = userPool.getCurrentUser();
        if (cognitoUser) {
            cognitoUser.signOut();
        }
        this.clearTokens();
    }

    /**
     * 現在のユーザー取得
     * 
     * @returns {Promise<Object>} ユーザー情報
     */
    static getCurrentUser() {
        return new Promise((resolve, reject) => {
            const cognitoUser = userPool.getCurrentUser();

            if (!cognitoUser) {
                reject(new Error('No user logged in'));
                return;
            }

            cognitoUser.getSession((err, session) => {
                if (err) {
                    console.error('Session error:', err);
                    reject(err);
                    return;
                }

                if (!session.isValid()) {
                    reject(new Error('Session is invalid'));
                    return;
                }

                cognitoUser.getUserAttributes((err, attributes) => {
                    if (err) {
                        console.error('Failed to get user attributes:', err);
                        reject(err);
                        return;
                    }

                    const userData = {};
                    attributes.forEach(attr => {
                        userData[attr.Name] = attr.Value;
                    });

                    resolve({
                        username: cognitoUser.getUsername(),
                        attributes: userData,
                        session: session
                    });
                });
            });
        });
    }

    /**
     * トークンリフレッシュ
     * 
     * @returns {Promise<Object>} 新しいトークン情報
     */
    static refreshToken() {
        return new Promise((resolve, reject) => {
            const cognitoUser = userPool.getCurrentUser();

            if (!cognitoUser) {
                reject(new Error('No user logged in'));
                return;
            }

            cognitoUser.getSession((err, session) => {
                if (err) {
                    console.error('Session error:', err);
                    reject(err);
                    return;
                }

                const refreshToken = session.getRefreshToken();
                cognitoUser.refreshSession(refreshToken, (err, session) => {
                    if (err) {
                        console.error('Token refresh failed:', err);
                        reject(err);
                        return;
                    }

                    const tokens = {
                        accessToken: session.getAccessToken().getJwtToken(),
                        idToken: session.getIdToken().getJwtToken(),
                        refreshToken: refreshToken.getToken()
                    };

                    this.saveTokens(tokens);
                    resolve(tokens);
                });
            });
        });
    }

    /**
     * パスワード変更
     * 
     * @param {string} oldPassword 現在のパスワード
     * @param {string} newPassword 新しいパスワード
     * @returns {Promise<string>} 変更結果
     */
    static changePassword(oldPassword, newPassword) {
        return new Promise((resolve, reject) => {
            const cognitoUser = userPool.getCurrentUser();

            if (!cognitoUser) {
                reject(new Error('No user logged in'));
                return;
            }

            cognitoUser.getSession((err, session) => {
                if (err) {
                    reject(err);
                    return;
                }

                cognitoUser.changePassword(oldPassword, newPassword, (err, result) => {
                    if (err) {
                        console.error('Password change failed:', err);
                        reject(err);
                        return;
                    }
                    resolve(result);
                });
            });
        });
    }

    /**
     * パスワードリセット開始
     * 
     * @param {string} email メールアドレス
     * @returns {Promise<Object>} リセット開始結果
     */
    static forgotPassword(email) {
        return new Promise((resolve, reject) => {
            const userData = {
                Username: email,
                Pool: userPool
            };
            const cognitoUser = new CognitoUser(userData);

            cognitoUser.forgotPassword({
                onSuccess: (data) => {
                    resolve(data);
                },
                onFailure: (err) => {
                    console.error('Forgot password failed:', err);
                    reject(err);
                }
            });
        });
    }

    /**
     * パスワードリセット確定
     * 
     * @param {string} email メールアドレス
     * @param {string} verificationCode 確認コード
     * @param {string} newPassword 新しいパスワード
     * @returns {Promise<string>} リセット結果
     */
    static confirmPassword(email, verificationCode, newPassword) {
        return new Promise((resolve, reject) => {
            const userData = {
                Username: email,
                Pool: userPool
            };
            const cognitoUser = new CognitoUser(userData);

            cognitoUser.confirmPassword(verificationCode, newPassword, {
                onSuccess: () => {
                    resolve('Password reset successful');
                },
                onFailure: (err) => {
                    console.error('Confirm password failed:', err);
                    reject(err);
                }
            });
        });
    }

    /**
     * トークンをローカルストレージに保存
     * 
     * @param {Object} tokens トークン情報
     */
    static saveTokens(tokens) {
        localStorage.setItem('cognito_access_token', tokens.accessToken);
        localStorage.setItem('cognito_id_token', tokens.idToken);
        localStorage.setItem('cognito_refresh_token', tokens.refreshToken);
    }

    /**
     * ローカルストレージからトークンを取得
     * 
     * @returns {Object|null} トークン情報
     */
    static getTokens() {
        const accessToken = localStorage.getItem('cognito_access_token');
        const idToken = localStorage.getItem('cognito_id_token');
        const refreshToken = localStorage.getItem('cognito_refresh_token');

        if (!accessToken || !idToken || !refreshToken) {
            return null;
        }

        return { accessToken, idToken, refreshToken };
    }

    /**
     * トークンをクリア
     */
    static clearTokens() {
        localStorage.removeItem('cognito_access_token');
        localStorage.removeItem('cognito_id_token');
        localStorage.removeItem('cognito_refresh_token');
    }

    /**
     * 認証状態チェック
     * 
     * @returns {boolean} ログイン済みかどうか
     */
    static isAuthenticated() {
        const cognitoUser = userPool.getCurrentUser();
        return cognitoUser !== null;
    }
}

// デフォルトエクスポート
export default CognitoAuthService;
