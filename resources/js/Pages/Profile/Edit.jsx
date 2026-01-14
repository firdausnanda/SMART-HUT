import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import { Head } from '@inertiajs/react';

export default function Edit({ auth, mustVerifyEmail, status }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex flex-col gap-1">
                    <h2 className="font-bold text-2xl text-gray-900 tracking-tight">Pengaturan Profil</h2>
                    <p className="text-sm text-gray-500 font-medium">Kelola informasi akun dan keamanan anda</p>
                </div>
            }
        >
            <Head title="Profile" />

            <div className="pb-2">
                <div className="max-w-7xl mx-auto space-y-8">
                    {/* Information Section */}
                    <div className="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md">
                        <div className="p-1 bg-gradient-to-r from-primary-500/10 via-transparent to-transparent"></div>
                        <div className="p-6 sm:p-10">
                            <UpdateProfileInformationForm
                                mustVerifyEmail={mustVerifyEmail}
                                status={status}
                                className="max-w-2xl"
                            />
                        </div>
                    </div>

                    {/* Password Section */}
                    <div className="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md">
                        <div className="p-1 bg-gradient-to-r from-primary-500/10 via-transparent to-transparent"></div>
                        <div className="p-6 sm:p-10">
                            <UpdatePasswordForm className="max-w-2xl" />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
