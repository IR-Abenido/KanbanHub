import GuestLayout from '@/Layouts/GuestLayout';
import { Link } from '@inertiajs/react';
import { Button, Typography } from '@material-tailwind/react';

export default function Welcome({ auth }) {

    return (
        <GuestLayout>
            <div className='p-4 md:max-w-[80%] text-center'>
                <Typography className="text-[2rem] mb-4" variant="h2" color="white">
                    Organize your work, your way
                </Typography>

                <Typography className="text-[1.5rem] mb-4" variant="h4" color="white">
                    Manage personal projects or collaborate with your team in one place
                </Typography>

                <Button
                    color="blue"
                    size='lg'
                >
                    <Link href='/register'>
                        Get Started
                    </Link>
                </Button>
            </div>
        </GuestLayout>
    );
}
