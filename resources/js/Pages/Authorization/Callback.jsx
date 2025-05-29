import { useEffect } from "react";
import {Link} from "@inertiajs/react";

export default function Callback({ downloadUrl}) {
    useEffect(() => {
        window.open(downloadUrl, "_blank");
    }, []);
    return (
        <div className="p-8">
            <h1 className="text-2xl font-bold mb-4">Authorization Successful</h1>
            <p className="mb-4">You can now download the file using the link below:
            <a href={downloadUrl} target="_blank" rel="noopener noreferrer" className="text-blue-500 hover:underline">
                Download File
            </a>
            </p>
            <p className="mb-4">You can also return to the <Link href="/" className="text-blue-500 hover:underline">home page</Link>.</p>
        </div>
    );
}
