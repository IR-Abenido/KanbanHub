import { IconButton, Menu, MenuHandler, MenuList } from "@material-tailwind/react";
import BoardTaskArchiveButton from "./BoardTaskArchiveButton";
import BoardTaskDeleteButton from "./BoardTaskDeleteButton";
import { useSelector } from "react-redux";
import { getUser } from "@/Features/user/userSlice";
import { getUserRoles } from "@/Features/board/boardSlice";

export default function BoardTaskRemoveOptions({ toggle, taskId, listId }) {
    const user = useSelector(getUser);
    const { workspaceRole, boardRole } = useSelector(state => getUserRoles(state, user.id));
    const canInteract = workspaceRole !== 'member' || boardRole !== 'member';
    return (
        <Menu
            animate={{
                mount: { y: 0 },
                unmount: { y: 25 }
            }}
        >
            <MenuHandler>
                <IconButton
                    variant="text"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                </IconButton>
            </MenuHandler>
            <MenuList className="z-[9999] p-0 flex flex-col justify-between">
                <BoardTaskArchiveButton taskId={taskId} listId={listId} toggle={toggle} />
                {canInteract && <BoardTaskDeleteButton taskId={taskId} listId={listId} toggle={toggle} />}
            </MenuList>
        </Menu>
    );
}
