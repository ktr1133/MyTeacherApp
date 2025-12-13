<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>管理者ログイン - MyTeacher</title>
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css'])
    
    <style>
        .admin-login-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="admin-login-bg min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- ロゴ・タイトル -->
        <div>
            <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-white dark:bg-gray-800 shadow-lg">
                <svg class="h-10 w-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                管理者ログイン
            </h2>
            <p class="mt-2 text-center text-sm text-indigo-100">
                MyTeacher 管理画面
            </p>
        </div>

        <!-- ログインフォーム -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-8">
            <form method="POST" action="{{ route('admin.login') }}" class="space-y-6">
                @csrf

                <!-- エラーメッセージ -->
                @if ($errors->any())
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    ログインに失敗しました
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- メールアドレス -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        メールアドレス
                    </label>
                    <div class="mt-1 relative">
                        <input 
                            id="email" 
                            name="email" 
                            type="email" 
                            autocomplete="email" 
                            required 
                            value="{{ old('email') }}"
                            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="admin@example.com"
                        >
                    </div>
                </div>

                <!-- パスワード -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        パスワード
                    </label>
                    <div class="mt-1 relative">
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            autocomplete="current-password" 
                            required
                            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="••••••••"
                        >
                    </div>
                </div>

                <!-- ログイン状態を保持 -->
                <div class="flex items-center">
                    <input 
                        id="remember" 
                        name="remember" 
                        type="checkbox" 
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    >
                    <label for="remember" class="ml-2 block text-sm text-gray-900 dark:text-white">
                        ログイン状態を保持する
                    </label>
                </div>

                <!-- ログインボタン -->
                <div>
                    <button 
                        type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                    >
                        ログイン
                    </button>
                </div>
            </form>
        </div>

        <!-- セキュリティ警告 -->
        <div class="text-center">
            <p class="text-xs text-indigo-100">
                <svg class="inline h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                </svg>
                このページは管理者専用です。5回ログインに失敗するとアカウントがロックされます。
            </p>
        </div>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>
