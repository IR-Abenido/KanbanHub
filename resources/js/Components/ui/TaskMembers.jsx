import { autoUpdate, flip, offset, shift, useFloating } from "@floating-ui/react";
import { Avatar, Button, IconButton, Typography } from "@material-tailwind/react";
import { useEffect, useRef, useState } from "react";

export default function TaskMembers({ task, setActivities, members, setMembers }) {
    const taskMembersRef = useRef(null);
    const [show, setShow] = useState(false);
    const [availableMembers, setAvailableMembers] = useState([]);

    const toggle = () => {
        setShow(prev => !prev);
    };

    const fetchAvailableMembers = async () => {
        try {
            const response = await axios.get(route('task.availableUsers.get'), {
                params: {
                    taskId: task.id
                }
            });

            setAvailableMembers(response.data.users);

        } catch (errors) {
            console.log(errors);
        }
    }

    const removeMember = async (memberId) => {
        try {
            const response = await axios.post(route('task.remove.user'), {
                taskId: task.id,
                userId: memberId
            });

            setMembers(prev => prev.filter(member => member.id !== memberId));
            setAvailableMembers(prev => [...prev, members.find(m => m.id === memberId)]);
            setActivities(prev => [response.data.activity, ...prev]);

        } catch (errors) {
            console.log(errors);
        }
    }

    const addMember = async (memberId) => {
        try {
            const response = await axios.post(route('task.add.user'), {
                taskId: task.id,
                userId: memberId
            });

            setMembers(prev => [...prev, response.data.user]);
            setAvailableMembers(prev => prev.filter(m => m.id !== response.data.user.id));
            setActivities(prev => [response.data.activity, ...prev]);

        } catch (errors) {
            console.log(errors);
        }
    }

    const { x, y, strategy, refs } = useFloating({
        placement: "bottom-start",
        middleware: [offset(6), flip(), shift({ padding: 5 })],
        whileElementsMounted: autoUpdate
    });

    useEffect(() => {
        fetchAvailableMembers();
    }, [task]);

    useEffect(() => {
        const handleOutsideClicks = (e) => {
            if (taskMembersRef.current && !taskMembersRef.current.contains(e.target)) {
                setShow(false);
            }
        };

        document.addEventListener("mousedown", handleOutsideClicks);
        return () => {
            document.removeEventListener('mousedown', handleOutsideClicks);
        };
    }, [taskMembersRef]);

    return (
        <div
            ref={taskMembersRef}
        >
            <Button
                variant="gradient"
                color="indigo"
                ref={refs.setReference}
                className="flex flex-row items-center gap-1 mr-2 p-0 py-3
                            justify-center text-[0.6rem] h-4 w-20 shadow-none hover:shadow-none"
                onClick={toggle}
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-4">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                Members
            </Button>
            <div
                ref={refs.setFloating}
                style={{ position: strategy, top: y ?? 0, left: x ?? 0, width: "max-content" }}
                className={`
                    mt-2 z-10 ${!show && 'hidden'}
                    bg-[#ebe9e9] rounded-md w-full min-w-[40%] max-w-[75%] md:max-w-[50%]
                    text-blue-gray-800
                `}
            >
                <div className="flex flex-col justify-end min-w-full">
                    <IconButton
                        onClick={toggle}
                        className="hover:bg-gray-400 rounded-sm mr-2 my-2
                        self-end"
                        size="sm"
                        variant="text"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </IconButton>
                    <div className="flex flex-col mx-2">
                        <div className="mb-2 w-[100%]">
                            <Typography
                                variant="h6"
                                color="blue-gray"
                            >
                                Members
                            </Typography>

                            {members?.length > 0 ? (
                                <div className="flex flex-col gap-1 mt-1">
                                    {members.map(member => (
                                        <div
                                            key={member?.id}
                                            className="flex flex-row justify-between"
                                        >
                                            <div className="flex flex-row justify-center items-center">
                                                <Avatar
                                                    src={member?.profilePicture || '/images/default-avatar.png'}
                                                    size="sm"
                                                />
                                                <Typography
                                                    variant="p"
                                                    color="gray"
                                                    className="ml-2"
                                                >
                                                    {member?.name}
                                                </Typography>
                                            </div>

                                            <IconButton
                                                onClick={() => removeMember(member?.id)}
                                                className="hover:scale-125 hover:shadow-none"
                                                size="sm"
                                                variant="text"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                            </IconButton>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <Typography color="gray" className="text-sm mt-1">
                                    No users
                                </Typography>
                            )}
                        </div>
                        <div className="mb-2 w-[100%]">
                            <Typography
                                variant="h6"
                                color="blue-gray"
                            >
                                Add Members
                            </Typography>

                            {availableMembers?.length > 0 ? (
                                <div className="flex flex-col gap-1 mt-1">
                                    {availableMembers.map(member => (
                                        <div
                                            key={member?.id}
                                            className="flex flex-row justify-between"
                                        >
                                            <div className="flex flex-row justify-center items-center">
                                                <Avatar
                                                    src={member?.profilePicture || '/images/default-avatar.png'}
                                                    size="sm"
                                                />
                                                <Typography
                                                    variant="p"
                                                    color="gray"
                                                    className="ml-2"
                                                >
                                                    {member?.name}
                                                </Typography>
                                            </div>

                                            <IconButton
                                                onClick={() => addMember(member?.id)}
                                                className="hover:scale-125 hover:shadow-none"
                                                size="sm"
                                                variant="text"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                            </IconButton>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <Typography color="gray" className="text-sm mt-1">
                                    No available board users
                                </Typography>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div >
    );

}
