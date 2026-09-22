<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = async () => {
    try {
        const axios = (await import('axios')).default.create({
            baseURL: 'https://atease.weofferwellness.co.uk',
            withCredentials: true,
        });

        // 1) Prime CSRF + session cookies on base domain
        await axios.get('/sanctum/csrf-cookie');

        // 2) Read CSRF token from cookie
        const getCookie = (name) => {
            const raw = document.cookie
                .split(';')
                .map((c) => c.trim())
                .find((c) => c.startsWith(name + '='));
            return raw ? raw.slice(name.length + 1) : '';
        };
        const xsrf = decodeURIComponent(getCookie('XSRF-TOKEN'));

        // 3) Build a cross-origin POST form directly to AtEase /login
        window.sessionStorage?.setItem('wow_auth_intent', 'login');
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = 'https://atease.weofferwellness.co.uk/login';
        const add = (k, v) => {
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = k;
            i.value = v;
            f.appendChild(i);
        };
        add('_token', xsrf);
        add('email', form.email);
        add('password', form.password);
        if (form.remember) add('remember', 'on');
        document.body.appendChild(f);
        // 4) Navigate immediately to AtEase (login handled there)
        f.submit();
    } catch (e) {
        form.setError('email', 'Login failed');
        form.setError('password', 'Check your credentials');
    } finally {
        // Let navigation proceed; clear password locally
        form.reset('password');
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400"
                        >Remember me</span
                    >
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
