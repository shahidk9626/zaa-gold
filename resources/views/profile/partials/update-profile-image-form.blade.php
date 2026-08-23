<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Image') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update or remove your profile photo.") }}
        </p>
    </header>

    <div class="mt-6 flex items-start gap-6 flex-wrap md:flex-nowrap">
        <!-- Current Image / Preview -->
        <div class="flex flex-col items-center mb-4 md:mb-0">
            <div class="w-32 h-32 rounded-lg overflow-hidden border-4 border-gray-200 shadow-sm bg-gray-100 flex items-center justify-content-center" style="width: 130px; height: 130px;">
                <img id="admin-avatar-preview" src="{{ $user->profile_image_url }}" alt="avatar" class="w-full h-full object-cover">
            </div>
            @if($user->profile_image)
                <form method="post" action="{{ route('profile.image.remove') }}" class="mt-3">
                    @csrf
                    @method('delete')
                    <x-secondary-button type="submit" class="text-xs text-red-600 hover:text-red-900">
                        {{ __('Remove Photo') }}
                    </x-secondary-button>
                </form>
            @endif
        </div>

        <!-- Upload Form -->
        <form method="post" action="{{ route('profile.image.upload') }}" enctype="multipart/form-data" class="space-y-4 flex-1 w-full">
            @csrf

            <div>
                <x-input-label for="profile_image" :value="__('Select Photo (Max 2MB: JPG, JPEG, PNG, WEBP)')" />
                <input id="profile_image" name="profile_image" type="file" accept="image/*" class="mt-2 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" required onchange="previewAdminAvatar(this)">
                <x-input-error class="mt-2" :messages="$errors->get('profile_image')" />
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Upload Photo') }}</x-primary-button>

                @if (session('status') === 'profile-image-updated')
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-600 font-bold">{{ __('Uploaded.') }}</p>
                @endif
                @if (session('status') === 'profile-image-removed')
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-600 font-bold">{{ __('Removed.') }}</p>
                @endif
            </div>
        </form>
    </div>
</section>

<script>
    function previewAdminAvatar(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('admin-avatar-preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
