import { getTask, getUserRoles, updateTaskCompletionStatus, updateTaskTitle } from "@/Features/board/boardSlice";
import { Button, Dialog, DialogBody, IconButton, Spinner, Tooltip, Typography } from "@material-tailwind/react";
import axios from "axios";
import { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import InputError from "../ui/InputError";
import TaskAttachments from "../ui/TaskAttachments";
import TaskDescription from "../ui/TaskDescription";
import TaskFilesUpload from "../ui/TaskFilesUpload";
import TaskActivities from "../ui/TaskActivities";
import TaskAddDueDate from "../ui/TaskAddDueDate";
import { getUser } from "@/Features/user/userSlice";
import BoardTaskRemoveOptions from "./BoardTaskRemoveOptions";

export default function BoardTaskModal({ show, toggle, listId, taskId }) {
    if (!listId || !taskId) return;
    const dispatch = useDispatch();
    const task = useSelector(state => getTask(state, listId, taskId));
    const user = useSelector(getUser);
    const [files, setFiles] = useState([]);
    const [activities, setActivities] = useState([]);

    const [enableTitleEdit, setEnableTitleEdit] = useState(false);
    const [fetchingActivities, setFetchingActivities] = useState(false);

    const toggleTitleEdit = () => {
        setErrors(null);
        setEnableTitleEdit((prev) => !prev);
    };

    const [titleData, setTitleData] = useState({
        taskId: taskId,
        title: task.title,
    });

    const [errors, setErrors] = useState();
    const [processing, setProcessing] = useState(false);
    const [markAsCompleteProcessing, setMarkAsCompleteProcessing] = useState(false);

    const getAttachments = async () => {
        try {
            const response = await axios.get(route('task.get.files'), {
                params: {
                    taskId: taskId
                }
            });
            setFiles(response.data.attachments);
        } catch (errors) {
            console.log(errors);
        }
    };

    const getActivities = async () => {
        setFetchingActivities(true);
        try {
            const response = await axios.get(route('task.get.activities'), {
                params: {
                    'taskId': task.id
                }
            })
            setActivities(response.data.activities);
        } catch (errors) {
            console.log(errors);
        } finally {
            setFetchingActivities(false);
        }
    };

    const submitTitleUpdate = async () => {
        if (task.title === titleData.title) {
            toggleTitleEdit();
            return;
        };

        setProcessing(true);

        try {
            const response = await axios.post(route('task.title.update'), titleData);
            dispatch(updateTaskTitle({
                listId: listId,
                taskId: taskId,
                title: titleData.title
            }))

            setActivities(prev => [response.data.activity, ...prev]);
            toggleTitleEdit();
            setProcessing(false);
        } catch (errors) {
            console.log(errors);
            setErrors(errors.response.data.errors);
            setProcessing(false);
        }
    };

    const submitMarkAsComplete = async () => {
        setMarkAsCompleteProcessing(true);

        try {
            const response = await axios.patch(route('task.completion.toggle'), { taskId });

            setActivities(prev => [response.data.activity, ...prev]);

            dispatch(updateTaskCompletionStatus({
                listId: listId,
                taskId: taskId,
                completed: !task.completed
            }));

            setMarkAsCompleteProcessing(false);
        } catch (errors) {
            console.log(errors);
            setMarkAsCompleteProcessing(false);
        }
    };

    useEffect(() => {
        getAttachments();
        getActivities();
    }, [taskId]);

    useEffect(() => {
        const taskChannel = window.Echo.private(`task.${taskId}`);

        taskChannel.listen('.task.move', (data) => {
            if (user.id !== data.senderId) {
                setActivities(prev => [data.activity, ...prev]);
            };
        });

        taskChannel.listen('.task.update.title', (data) => {
            if (data.senderId !== user.id) {
                dispatch(updateTaskTitle({
                    listId: data.listId,
                    taskId: data.taskId,
                    title: data.updatedTitle
                }));
                setActivities(prev => [data.activity, ...prev]);
            }
        });

        taskChannel.listen('.task.update.completed', (data) => {
            console.log(data);
            if (data.senderId !== user.id) {
                dispatch(updateTaskCompletionStatus({
                    listId: data.listId,
                    taskId: data.taskId,
                    completed: data.completed
                }))
                setActivities(prev => [data.activity, ...prev]);
            }
        });

        taskChannel.listen('.task.update.description', (data) => {
            if (data.senderId !== user.id) {
                setActivities(prev => [data.activity, ...prev]);
            }
        });


        taskChannel.listen('.task.deadline.update', (data) => {
            if (data.senderId !== user.id) {
                setActivities(prev => [data.activity, ...prev]);
            }
        });

        taskChannel.listen('.task.deadline.remove', (data) => {
            if (data.senderId !== user.id) {
                setActivities(prev => [data.activity, ...prev]);
            }
        });

        return () => {
            taskChannel.stopListening(".task.move");
            taskChannel.stopListening(".task.update.title");
            taskChannel.stopListening(".task.update.completed");
            taskChannel.stopListening(".task.update.description");
            taskChannel.stopListening(".task.deadline.update");
            taskChannel.stopListening(".task.deadline.remove");
        };

    }, [listId, taskId, dispatch]);

    return (
        <>
            <Dialog
                open={show}
                handler={toggle}
                size='md'
                animate={{
                    mount: { scale: 1, y: 0 },
                    unmount: { scale: 1, y: 100 }
                }}
                className="h-[85%] overflow-y-auto"
            >
                <DialogBody>
                    <div
                        className="flex flex-row justify-between items-center
                    text-blue-gray-900">
                        <div className="flex flex-row gap-2 items-baseline w-full">
                            {!task.completed ?
                                <Button
                                    size="sm"
                                    disabled={markAsCompleteProcessing}
                                    color="amber"
                                    variant="filled"
                                    onClick={submitMarkAsComplete}
                                    className="shadow-none hover:shadow-none
                                        flex justify-center items-center"
                                >
                                    {markAsCompleteProcessing &&
                                        <span className="w-4 mr-2 flex items-center justify-center">
                                            <Spinner className="h-4 w-4" />
                                        </span>
                                    }
                                    Ongoing
                                </Button>
                                :
                                <Button
                                    size="sm"
                                    disabled={markAsCompleteProcessing}
                                    color="green"
                                    variant="filled"
                                    onClick={submitMarkAsComplete}
                                    className="shadow-none hover:shadow-none
                                        flex justify-center items-center"
                                >
                                    {markAsCompleteProcessing &&
                                        <span className="w-4 mr-2 flex items-center justify-center">
                                            <Spinner className="h-4 w-4" />
                                        </span>
                                    }
                                    Finished
                                </Button>
                            }
                            <div
                                className="
                                flex flex-col w-full mr-2
                                "
                            >
                                {enableTitleEdit ?
                                    <input
                                        autoFocus
                                        className="
                                    py-0 px-1 w-full rounded-sm mb-2
                                    text-blue-gray-900 font-semibold"
                                        type="text"
                                        value={titleData.title}
                                        onChange={(e) => setTitleData({
                                            ...titleData,
                                            title: e.target.value
                                        })}
                                        onClick={(e) => e.stopPropagation()}
                                        onBlur={() => {
                                            if (processing) return;
                                            setTitleData((prev) => ({
                                                ...prev,
                                                title: prev.title.trim(),
                                            }));
                                            submitTitleUpdate();
                                        }}
                                    />
                                    :
                                    <Typography
                                        onClick={toggleTitleEdit}
                                        className="w-fit"
                                        variant="h6"
                                        color="blue-gray"
                                    >
                                        {titleData.title}
                                    </Typography>
                                }
                                {errors && <InputError message={errors.title} />}
                            </div>
                        </div>
                        <BoardTaskRemoveOptions toggle={toggle} listId={listId} taskId={taskId} />
                        <IconButton
                            color="blue-gray"
                            className="ml-2 h-6 w-6"
                            onClick={(e) => {
                                e.stopPropagation();
                                toggle();
                            }}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-4">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </IconButton>
                    </div>
                    <div className="flex flex-col-reverse md:flex-row gap-2 flex-1 mt-4 text-blue-gray-900 min-h-0 w-full">
                        <div className="w-full  flex flex-col">
                            <div className="flex flex-col">
                                <div className="flex flex-col flex-grow">
                                    <div className="flex flex-row">
                                        <TaskAddDueDate
                                            task={task}
                                            setActivities={setActivities}
                                        />
                                        <TaskFilesUpload
                                            listId={listId}
                                            taskId={taskId}
                                            setFiles={setFiles}
                                            setActivities={setActivities}
                                        />
                                    </div>
                                    <TaskDescription
                                        task={task}
                                        setActivities={setActivities}

                                    />
                                    <TaskAttachments
                                        files={files}
                                        setFiles={setFiles}
                                        setActivities={setActivities}
                                    />
                                </div>
                                {fetchingActivities ?
                                    <Spinner className="m-auto" />
                                    :
                                    <TaskActivities
                                        task={task}
                                        activities={activities}
                                        setActivities={setActivities}
                                        attachmentNotEmpty={files?.length > 0}
                                        refreshFiles={getAttachments}
                                    />
                                }
                            </div>
                        </div>
                    </div>
                </DialogBody>
            </Dialog >
        </>
    );
}
