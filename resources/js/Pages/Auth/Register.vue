<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = async () => {
    try {
        const axios = (await import('axios')).default.create({
            baseURL: 'https://atease.weofferwellness.co.uk',
            withCredentials: true,
        });

        // 1) Prime CSRF cookie on base domain
        await axios.get('/sanctum/csrf-cookie');

        // 2) Read CSRF token cookie (scoped to .weofferwellness.co.uk)
        const getCookie = (name) => {
            const raw = document.cookie
                .split(';')
                .map((c) => c.trim())
                .find((c) => c.startsWith(name + '='));
            return raw ? raw.slice(name.length + 1) : '';
        };
        const xsrf = decodeURIComponent(getCookie('XSRF-TOKEN'));

        // 3) Cross-origin form POST directly to AtEase /register
        window.sessionStorage?.setItem('wow_auth_intent', 'sign_up');
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = 'https://atease.weofferwellness.co.uk/register';
        const add = (k, v) => {
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = k;
            i.value = v;
            f.appendChild(i);
        };
        add('_token', xsrf);
        add('name', form.name);
        add('email', form.email);
        add('password', form.password);
        add('password_confirmation', form.password_confirmation);
        document.body.appendChild(f);
        f.submit();
    } catch (e) {
        form.setError('email', 'Registration failed');
    } finally {
        form.reset('password', 'password_confirmation');
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
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
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
