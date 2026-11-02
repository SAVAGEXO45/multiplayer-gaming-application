<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your public identity, avatar and bio"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            force-form-data
            v-slot="{ errors, processing }"
        >
            <div class="flex items-center gap-4">
                <img
                    v-if="user.avatar_url"
                    :src="user.avatar_url"
                    :alt="user.name"
                    class="size-16 rounded-2xl border border-white/10 object-cover"
                />
                <div
                    v-else
                    class="flex size-16 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-lg font-bold text-white"
                >
                    {{ user.name.charAt(0) }}
                </div>
                <div class="grid gap-1">
                    <Label for="avatar">Avatar</Label>
                    <input
                        id="avatar"
                        type="file"
                        name="avatar"
                        accept="image/png,image/jpeg,image/webp"
                        class="text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-white/15"
                    />
                    <p class="text-xs text-muted-foreground">
                        JPG, PNG or WebP, up to 2 MB.
                    </p>
                    <InputError :message="errors.avatar" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="username">Gamer tag</Label>
                <Input
                    id="username"
                    class="mt-1 block w-full"
                    name="username"
                    :default-value="user.username"
                    required
                    autocomplete="username"
                    placeholder="pixel_hero"
                />
                <InputError class="mt-2" :message="errors.username" />
            </div>

            <div class="grid gap-2">
                <Label for="bio">Bio</Label>
                <textarea
                    id="bio"
                    name="bio"
                    rows="3"
                    maxlength="280"
                    :value="user.bio ?? ''"
                    class="mt-1 block w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    placeholder="Tell the arcade who you are"
                />
                <InputError class="mt-2" :message="errors.bio" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
