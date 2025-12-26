<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const activeSection = ref('intro');

const sections = [
    { id: 'intro', title: 'Introduction' },
    { id: 'mysites', title: 'My Sites & Builds' },
    { id: 'logs', title: 'Logs & Monitoring' },
    { id: 'env', title: 'Environment Variables' },
    { id: 'security', title: 'Profile & Security' },
];

const scrollToSection = (id) => {
    activeSection.value = id;
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<template>
    <Head title="Documentation" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">User Documentation</h2>
        </template>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Navigation -->
            <aside class="w-full lg:w-64 shrink-0">
                <div class="sticky top-24 space-y-1">
                    <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                        Contents
                    </p>
                    <button
                        v-for="section in sections"
                        :key="section.id"
                        @click="scrollToSection(section.id)"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors text-left"
                        :class="activeSection === section.id 
                            ? 'bg-primary-50 text-primary' 
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                    >
                        <div 
                            class="w-1.5 h-1.5 rounded-full transition-colors"
                            :class="activeSection === section.id ? 'bg-primary-600' : 'bg-transparent'"
                        ></div>
                        {{ section.title }}
                    </button>
                </div>
            </aside>

            <!-- Main Content -->
            <article class="flex-1 min-w-0 space-y-12 pb-12">
                
                <!-- Introduction -->
                <section id="intro" class="scroll-mt-24">
                    <div class="prose prose-slate max-w-none">
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
                </section>

                <hr class="border-slate-200" />

                <!-- My Sites -->
                <section id="mysites" class="scroll-mt-24">
                    <div class="prose prose-slate max-w-none">
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
                </section>

                <hr class="border-slate-200" />

                <!-- Logs -->
                <section id="logs" class="scroll-mt-24">
                     <div class="prose prose-slate max-w-none">
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
                </section>

                <hr class="border-slate-200" />

                <!-- Env Variables -->
                <section id="env" class="scroll-mt-24">
                     <div class="prose prose-slate max-w-none">
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

                        <h3 class="text-xl font-semibold text-slate-800 mt-8">Step-by-Step Guide</h3>

                        <h4 class="text-lg font-semibold text-slate-700 mt-6">1. Create a Variable</h4>
                        <p>Navigate to <strong>Env Variables</strong> and click "Add Variable":</p>
                        <ul class="list-disc pl-5 space-y-2 text-slate-600">
                            <li><strong>Variable Name:</strong> Use UPPERCASE_SNAKE_CASE (e.g., <code>API_KEY</code>, <code>DB_PASSWORD</code>)</li>
                            <li><strong>Variable Value:</strong> The actual value (can be JSON, text, or numbers)</li>
                            <li><strong>Group Name (Optional):</strong> Enter if this is group-scoped (e.g., <code>PROD_SERVER</code>)</li>
                            <li><strong>My Site (Optional):</strong> Select if this is site-specific</li>
                        </ul>

                        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 my-4 rounded-r-lg">
                            <p class="text-amber-800 text-sm m-0">
                                <strong>Note:</strong> You cannot set both Group Name <strong>AND</strong> My Site. Choose one or leave both empty for a global variable.
                            </p>
                        </div>

                        <h4 class="text-lg font-semibold text-slate-700 mt-6">2. Use in Your .env.example File</h4>
                        <p>Add placeholders in your project's <code>.env.example</code> file:</p>

                        <div class="bg-slate-900 rounded-xl p-5 my-4 overflow-x-auto">
                            <pre class="text-xs text-slate-100 m-0"><code># Global Variables (shared across all sites)
APP_KEY=###APP_KEY
STRIPE_SECRET=###STRIPE_SECRET

# Group Variables (e.g., for staging vs production)
DB_HOST=###PROD_SERVER###DB_HOST
DB_PORT=###PROD_SERVER###DB_PORT
DB_NAME=###PROD_SERVER###DB_NAME

# Site-Specific Variables (unique per site)
OAUTH_CLIENT_ID=###SITE_NAME###OAUTH_CLIENT_ID
OAUTH_CLIENT_SECRET=###SITE_NAME###OAUTH_CLIENT_SECRET
SITE_DOMAIN=###SITE_NAME###DOMAIN</code></pre>
                        </div>

                        <h4 class="text-lg font-semibold text-slate-700 mt-6">3. Build the Site</h4>
                        <p>When you trigger a build, the placeholders are automatically replaced:</p>

                        <div class="bg-white border border-slate-200 rounded-xl p-5 my-4">
                            <p class="text-sm font-semibold text-slate-700 mb-3">Example Result:</p>
                            <div class="grid md:grid-cols-2 gap-4 text-xs font-mono">
                                <div>
                                    <p class="text-slate-500 mb-2">Before (in .env.example):</p>
                                    <code class="bg-slate-100 p-2 rounded block">DB_HOST=###PROD_SERVER###DB_HOST</code>
                                </div>
                                <div>
                                    <p class="text-slate-500 mb-2">After (in .env):</p>
                                    <code class="bg-green-100 p-2 rounded block text-green-800">DB_HOST=mysql-prod.example.com</code>
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

                        <h3 class="text-xl font-semibold text-slate-800 mt-8">Troubleshooting</h3>
                        <div class="space-y-4">
                            <div class="border border-slate-200 rounded-lg p-4">
                                <p class="font-semibold text-slate-800 mb-2">❓ Placeholder not replaced after build</p>
                                <ul class="text-sm text-slate-600 space-y-1 ml-4">
                                    <li>• Check variable name matches exactly (case-sensitive)</li>
                                    <li>• Verify correct scope (global/group/site) is set</li>
                                    <li>• Check build logs for warnings about missing variables</li>
                                </ul>
                            </div>
                            <div class="border border-slate-200 rounded-lg p-4">
                                <p class="font-semibold text-slate-800 mb-2">❓ Cannot save variable with both Group and Site</p>
                                <ul class="text-sm text-slate-600 space-y-1 ml-4">
                                    <li>• This is intentional - scopes are mutually exclusive</li>
                                    <li>• Choose one scope or leave both empty for global</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <hr class="border-slate-200" />

                <!-- Security -->
                <section id="security" class="scroll-mt-24">
                     <div class="prose prose-slate max-w-none">
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
                </section>

            </article>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Smooth scrolling for anchor links */
html {
    scroll-behavior: smooth;
}
</style>
