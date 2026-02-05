import { Button, Typography } from "@material-tailwind/react";
import { Head, Link, usePage } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    const { url } = usePage()

    return (
        <div className="min-h-screen bg-[#263238]">
            <Head title="KanbanHub" />
            <div className="flex flex-row justify-between items-center p-4">
                <Link href="/">
                    <Typography className="text-3xl" variant="h1" color="white">KanbanHub</Typography>
                </Link>
                {url !== '/login' &&
                    <Button
                        variant="text"
                        size="lg"
                        color="white"
                    >

                        <Link href={route('login')}>
                            Sign in
                        </Link>
                    </Button>
                }
            </div>
            <div className="flex flex-col justify-center items-center h-[calc(100vh-5rem)]">
                {children}
            </div>
        </div>
    );
}
