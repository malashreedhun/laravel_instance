import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';



export default function Confirmation({ auth }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Confirmation page</h2>}
        >
            <Head title="Confirmation page" />
            <div className="p-5"  style={{ backgroundColor: 'green', display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
                <h1 className="text-base font-semibold leading-6 text-gray-900">You have successfully submitted the form.</h1>
            </div>

        </AuthenticatedLayout>
    );
}
