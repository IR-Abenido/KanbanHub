import { Button, Dialog, DialogBody, Typography } from "@material-tailwind/react";
import axios from "axios";
import { useState } from "react";
import TextInput from "../ui/TextInput";
import InputError from "../ui/InputError";
import { router, usePage } from "@inertiajs/react";

export default function ProfileDelete({ }) {
    const { props } = usePage();
    const user = props.auth.user;
    const [show, setShow] = useState(false);
    const [data, setData] = useState({
        id: user.id,
        password: ''
    });

    const [errors, setErrors] = useState(null);
    const toggle = () => {
        setShow(!show);
    };

    const submit = async () => {
        try {
            await axios.post(route('account.delete'), data);
            router.visit('/');
        } catch (errors) {
            setErrors(errors.response.data.errors);
        }
    };

    return (
        <div>
            <Typography variant="h3" color="white" className="pb-2">
                Delete Account
            </Typography>
            <Typography variant="paragraph" color="white">
                Deleting your account is permanent. All your data, services, and content will be permanently removed and cannot be recovered. Some minimal information may be retained as required by law or for security purposes.
            </Typography>
            <Button className="mt-4 w-full" color="red" onClick={toggle}>
                Delete
            </Button>
            <Dialog
                size="xs"
                open={show}
                handler={toggle}
                animate={{
                    mount: { scale: 1, y: 0 },
                    unmount: { scale: 0.9, y: -100 },
                }}
            >
                <DialogBody className="p-4 text-justify">
                    <Typography variant="paragraph">
                        This action is permanent. Once deleted, your account and all associated data will be irreversibly removed. Are you sure you want to continue?
                    </Typography>
                    <Typography variant="paragraph">
                        Please enter your password to proceed with your account deactivation
                    </Typography>
                    <TextInput
                        type="password"
                        className="w-full mt-4"
                        onChange={(e) => setData({ ...data, password: e.target.value })}
                    />
                    {errors && <InputError className="text-left mt-2" message={errors.password} />}
                    <div className="flex flex-row gap-2 justify-center mt-4">
                        <Button className="w-full" color="red" onClick={submit}>Proceed</Button>
                        <Button className="w-full" color="gray" onClick={toggle}>Cancel</Button>
                    </div>
                </DialogBody>
            </Dialog>
        </div>
    );
}
