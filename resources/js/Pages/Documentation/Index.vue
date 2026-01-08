<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// Tab state
const activeTab = ref('intro');

const tabs = [
    { 
        id: 'intro', 
        name: 'Introduction', 
        icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'
    },
    { 
        id: 'mysites', 
        name: 'My Sites & Builds', 
        icon: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9'
    },
    { 
        id: 'logs', 
        name: 'Logs & Monitoring', 
        icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
    },
    { 
        id: 'env', 
        name: 'Environment Variables', 
        icon: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'
    },
    { 
        id: 'security', 
        name: 'Profile & Security', 
        icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'
    },
];
</script>

<template>
    <Head title="Documentation" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-slate-800 leading-tight">Documentation</h2>
                    <p class="text-sm text-slate-500">Complete guide for Francis Build Manager</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tabs Container -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-100 max-w-full">
                <!-- Tab Headers -->
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100 overflow-hidden">
                    <div class="flex overflow-x-auto scrollbar-hide">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'flex items-center gap-1.5 sm:gap-2 px-3 sm:px-6 py-3 sm:py-4 font-medium text-xs sm:text-sm whitespace-nowrap transition-all duration-200 border-b-2',
                                activeTab === tab.id
                                    ? 'text-primary border-primary bg-white'
                                    : 'text-slate-600 border-transparent hover:text-slate-900 hover:bg-slate-50'
                            ]"
                        >
                            <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                            </svg>
                            <span class="hidden sm:inline">{{ tab.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-6 sm:p-8">
                    
                    <!-- Introduction Tab -->
                    <div v-show="activeTab === 'intro'" class="animate-fade-in prose prose-slate max-w-none">
                        <h2 class="text-3xl font-bold text-slate-900 mb-6">Introduction</h2>
                        <p class="text-lg text-slate-600 leading-relaxed">
                            Welcome to the <strong class="text-indigo-600">Francis Build Manager</strong> documentation. 
                            This system allows you to manage website deployments, monitor application logs via PM2, configure environment variables, and manage user access securely.
                        </p>
                        <div class="grid sm:grid-cols-2 gap-4 mt-8 not-prose">
                            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-200">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 mb-4">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-slate-900 mb-2">Automated Builds</h3>
                                <p class="text-sm text-slate-500">Trigger builds from source code with automated script generation and logs.</p>
                            </div>
                            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-200">
                                <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 mb-4">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-slate-900 mb-2">Real-time Logs</h3>
                                <p class="text-sm text-slate-500">Monitor PM2 application logs with advanced parsing and search capabilities.</p>
                            </div>
                        </div>
                    </div>

                    <!-- My Sites Tab -->
                    <div v-show="activeTab === 'mysites'" class="animate-fade-in prose prose-slate max-w-none">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-900 m-0">My Sites & Builds</h2>
                        </div>
                        
                        <h3 class="text-xl font-semibold text-slate-800">Adding a New Site</h3>
                        <p>Only <strong>Super Admins</strong> can create new sites. Navigate to "My Sites" and click "Create Site".</p>
                        <ul class="list-disc pl-5 space-y-2 text-slate-600">
                            <li><strong>Site Name:</strong> Unique identifier for your project.</li>
                            <li><strong>Source Path:</strong> Absolute path to the source code folder on the server (e.g., <code>/var/www/html/my-project</code>).</li>
                            <li><strong>Include PM2:</strong> Check this if the application is managed by PM2 (e.g., Node.js apps).</li>
                            <li><strong>Port (PM2):</strong> Required if PM2 is enabled. The port the app runs on.</li>
                        </ul>

                        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 my-6 rounded-r-lg">
                            <p class="text-amber-800 text-sm m-0">
                                <strong>Note:</strong> The system automatically generates a build script (<code>.sh</code> file) upon creation.
                            </p>
                        </div>

                        <h3 class="text-xl font-semibold text-slate-800 mt-8">Triggering a Build</h3>
                        <p>Admins and Super Admins can trigger builds manually via the UI or using APIs.</p>
                        <ol class="list-decimal pl-5 space-y-2 text-slate-600">
                            <li>Go to the site details page.</li>
                            <li>Click the <strong>Build Site</strong> button.</li>
                            <li>The build request is added to a <strong>Queue</strong>. You can monitor its status (Pending, Processing, Done, Failed) in the "Build History" table.</li>
                            <li>Once processing starts, logs are streamed to the "Log Build" tab.</li>
                        </ol>

                        <h3 class="text-xl font-semibold text-slate-800 mt-8">Editing Environment (.env)</h3>
                        <p>
                            To modify a site's specific .env file, you can edit the source code file directly or update the site settings to map specific variables. 
                            The build process will automatically copy the appropriate source env file (e.g., <code>.env.prod</code>) to <code>.env</code> during deployment.
                        </p>
                    </div>

                    <!-- Logs Tab -->
                    <div v-show="activeTab === 'logs'" class="animate-fade-in prose prose-slate max-w-none">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-rose-100 rounded-lg text-rose-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-900 m-0">Logs & Monitoring</h2>
                        </div>

                        <p>Navigate to <strong>Log PM2</strong> to view application logs. The system scans the directory defined in <code>LOG_PM2_PATH</code>.</p>

                        <div class="grid md:grid-cols-2 gap-6 my-6 not-prose">
                            <div class="border border-slate-200 rounded-xl p-5 hover:border-indigo-300 transition-colors">
                                <h4 class="font-semibold text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    Raw Logs
                                </h4>
                                <p class="text-sm text-slate-500 mt-2">
                                    View the exact file content as stored on the disk. Useful for full context debugging or when advanced parsing fails.
                                </p>
                            </div>
                            <div class="border border-slate-200 rounded-xl p-5 hover:border-indigo-300 transition-colors">
                                <h4 class="font-semibold text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    Advance Logs
                                </h4>
                                <p class="text-sm text-slate-500 mt-2">
                                    A structured table view that parses logs to show Timestamp, Level (Info/Error), and Message. Includes search and filtering.
                                </p>
                            </div>
                        </div>

                        <h3 class="text-xl font-semibold text-slate-800">Search & Filter</h3>
                        <p>In Advance View, use the search bar to filter logs by keywords. This performs a case-insensitive search within the log messages.</p>
                    </div>

                    <!-- Environment Variables Tab -->
                    <div v-show="activeTab === 'env'" class="animate-fade-in prose prose-slate max-w-none">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-emerald-100 rounded-lg text-emerald-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-900 m-0">Environment Variables</h2>
                        </div>

                        <p class="text-lg text-slate-600 leading-relaxed">
                            The <strong>Environment Variables Manager</strong> provides a secure, centralized way to manage configuration values across your sites. 
                            It supports <strong>three scopes</strong>: Global, Group, and Site-Specific variables.
                        </p>

                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6 rounded-r-lg">
                            <p class="text-blue-900 text-sm m-0">
                                <strong>Security:</strong> All variable values are encrypted at rest in the database and only decrypted during build time.
                            </p>
                        </div>

                        <h3 class="text-xl font-semibold text-slate-800 mt-8">How It Works</h3>
                        <p>When a site is built, the system:</p>
                        <ol class="list-decimal pl-5 space-y-2 text-slate-600">
                            <li>Copies the source env file (e.g., <code>.env.example</code>) to <code>.env</code></li>
                            <li>Scans for placeholder patterns like <code>###VARIABLE_NAME</code></li>
                            <li>Queries the database for matching variables based on scope</li>
                            <li>Replaces placeholders with decrypted values</li>
                        </ol>

                        <h3 class="text-xl font-semibold text-slate-800 mt-8">Variable Scopes</h3>
                        
                        <div class="grid md:grid-cols-3 gap-4 my-6 not-prose">
                            <div class="border-2 border-green-200 rounded-xl p-5 bg-green-50">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                    <h4 class="font-bold text-green-900 m-0">Global</h4>
                                </div>
                                <p class="text-sm text-green-800 mb-3">Shared across all sites. Use for common API keys, base URLs, or application secrets.</p>
                                <div class="bg-white rounded-lg p-3 border border-green-200">
                                    <p class="text-xs font-mono text-slate-600 mb-1">Pattern:</p>
                                    <code class="text-xs bg-slate-100 px-2 py-1 rounded block">###VAR_NAME</code>
                                </div>
                            </div>

                            <div class="border-2 border-purple-200 rounded-xl p-5 bg-purple-50">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                                    <h4 class="font-bold text-purple-900 m-0">Group</h4>
                                </div>
                                <p class="text-sm text-purple-800 mb-3">Scoped to a logical group (e.g., DEV_SERVER, PROD_SERVER). Use for environment-specific configs.</p>
                                <div class="bg-white rounded-lg p-3 border border-purple-200">
                                    <p class="text-xs font-mono text-slate-600 mb-1">Pattern:</p>
                                    <code class="text-xs bg-slate-100 px-2 py-1 rounded block">###GROUP###VAR_NAME</code>
                                </div>
                            </div>

                            <div class="border-2 border-blue-200 rounded-xl p-5 bg-blue-50">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                    <h4 class="font-bold text-blue-900 m-0">Site-Specific</h4>
                                </div>
                                <p class="text-sm text-blue-800 mb-3">Unique to a single site. Use for site-specific secrets or configurations.</p>
                                <div class="bg-white rounded-lg p-3 border border-blue-200">
                                    <p class="text-xs font-mono text-slate-600 mb-1">Pattern:</p>
                                    <code class="text-xs bg-slate-100 px-2 py-1 rounded block">###SITE_NAME###VAR_NAME</code>
                                    <p class="text-xs text-blue-700 mt-2">* <code>SITE_NAME</code> is a reserved keyword</p>
                                </div>
                            </div>
                        </div>

                        <h3 class="text-xl font-semibold text-slate-800 mt-8">Best Practices</h3>
                        <ul class="list-disc pl-5 space-y-2 text-slate-600">
                            <li><strong>Global Variables:</strong> Use for truly shared values like third-party API keys, application secrets</li>
                            <li><strong>Group Variables:</strong> Perfect for environment-specific configs (dev/staging/prod database credentials)</li>
                            <li><strong>Site-Specific:</strong> Use when each site needs unique values (OAuth credentials, custom domains)</li>
                            <li><strong>Naming Convention:</strong> Keep variable names descriptive and use UPPERCASE_SNAKE_CASE</li>
                            <li><strong>Security:</strong> Never commit actual secrets to version control - always use placeholders</li>
                        </ul>
                    </div>

                    <!-- Profile & Security Tab -->
                    <div v-show="activeTab === 'security'" class="animate-fade-in prose prose-slate max-w-none">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-purple-100 rounded-lg text-purple-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-900 m-0">Profile & Security</h2>
                        </div>

                        <h3 class="text-xl font-semibold text-slate-800">Two-Factor Authentication (2FA)</h3>
                        <p>
                            For enhanced security, all users are encouraged to enable 2FA. 
                            Go to <strong>Profile > Two Factor Authentication</strong>, click "Enable", and scan the QR code with an authenticator app (Google Authenticator, Authy).
                        </p>

                        <h3 class="text-xl font-semibold text-slate-800 mt-8">API Tokens</h3>
                        <p>
                            (Admin Only) API tokens allow external scripts or CI/CD pipelines to trigger builds programmatically.
                        </p>
                        <ul class="list-disc pl-5 space-y-2 text-slate-600">
                            <li>Go to <strong>Profile > API Tokens</strong>.</li>
                            <li>Create a new token with a descriptive name.</li>
                            <li>Copy the token immediately (it is only shown once).</li>
                            <li>Use the token as a Bearer Token in authorization headers when calling API endpoints.</li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
