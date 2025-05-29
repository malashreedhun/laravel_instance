
// EDIT THIS TO EDIT TRANSFER AUTHORIZATION FORMMMMMMMMMMMMMMMM
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
// import {Head, Link} from '@inertiajs/react';
import InputLabel from "@/Components/InputLabel.jsx";
import TextInput from "@/Components/TextInput.jsx";
import InputError from "@/Components/InputError.jsx";
import PrimaryButton from "@/Components/PrimaryButton.jsx";
import {useEffect} from "react";
import {XCircleIcon} from "@heroicons/react/20/solid/index.js";
import React, { useState } from 'react';
import { useForm } from '@inertiajs/inertia-react';
// import {inertia} from "@inertiajs/react";

export default function Form({ auth, pageTitle, formUrl }) {
  const { data, setData, processing } = useForm({
    signer_name:  '',
    signer_email: '',
  });
  console.log('Form data:', data);
  const [envelopeId, setEnvelopeId] = useState(null);


//SUBMIT HANDLER
  const submit = async e => {
  e.preventDefault();

  try {
    const res = await fetch(formUrl, {
      method: 'POST',
      headers: {
        'Accept':       'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      credentials: 'same-origin',
      body: JSON.stringify(data)
    });

    if (!res.ok) {
      // server sent a 4xx/5xx
      const text = await res.text();
      throw new Error(`HTTP ${res.status}: ${text}`);
    }

    const json = await res.json();
    console.log('📮 Got back JSON:', json);

    if (json.success) {
      setEnvelopeId(json.envelopeId);      // ← pull the returned ID into state
    } else {
      console.error('Envelope error payload:', json);
      alert('Error from server: ' + JSON.stringify(json));
    }
  }
  catch (err) {
    console.error('Fetch error:', err);
    alert('Network or server error: ' + err.message);
  }
};
//SUBMIT HANDLER END


  return (
<form onSubmit={submit}>
<input
        type="text"
        name="signer_name"
        value={data.signer_name}
        onChange={e => setData('signer_name', e.target.value)}
        placeholder="Your Name"
        required
      />
<input
        type="email"
        name="signer_email"
        value={data.signer_email}
        onChange={e => setData('signer_email', e.target.value)}
        placeholder="you@example.com"
        required
      />
<button type="submit" disabled={processing}>Send by Email {envelopeId} </button>
      {envelopeId && (
        <div className="mt-4 p-3 border rounded bg-green-50 text-green-800">
            Envelope created successfully! ID: <code>{envelopeId}</code>
        </div>
      )}
</form>
  );
}

// export default function Form({auth, pageTitle, pageDescription, pageData, formUrl}) {
//     const {data, setData, patch, processing, errors, reset} = useForm({
//         title       : (pageData !== null) ? pageData.title : '',
//         ISBN_10     : (pageData !== null) ? pageData.ISBN_10 : '',
//         ISBN_13     : (pageData !== null) ? pageData.ISBN_13 : '',
//         author      : (pageData !== null) ? pageData.author : '',
//     });

//     useEffect(() => {
//         return () => {
//             reset('name', 'ssn', 'ISBN_13', 'author');
//         };
//     }, []);

//     const submit = (e) => {
//         e.preventDefault();
//         patch(formUrl);
//     };
//     return (
//         <AuthenticatedLayout
//             user={auth.user}
//         >
//             <Head title={pageTitle}/>
//             <div className="m-5 p-5 flow-root shadow sm:rounded-lg">
//                 <div className="sm:flex sm:items-center border-b pb-3">
//                     <div className="sm:flex-auto">
//                         <h1 className="text-base font-semibold leading-6 text-gray-900">{pageTitle}</h1>
//                         {pageDescription !== '' ? (<>
//                             <p className="mt-2 text-sm text-gray-700">
//                                 {pageDescription}
//                             </p>
//                         </>) : '' }
//                     </div>
//                 </div>
//                 <form onSubmit={submit} className="space-y-6">

//                     <div className={`grid grid-cols-2 gap-4 py-4`}>
//                         <div>
//                             <InputLabel htmlFor="title" value="PLEASE ENTER YOUR NAME HEREEEEE"
//                                         className="block text-sm font-medium leading-6 text-gray-900"/>
//                             <div className="mt-2">
//                                 <TextInput
//                                     id="title"
//                                     name="title"
//                                     type={'text'}
//                                     placeholder={'Enter your name here'}
//                                     value={data.title}
//                                     className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
//                                     autoComplete="title"
//                                     isFocused={true}
//                                     onChange={(e) => setData('title', e.target.value)}
//                                 />
//                             </div>
//                             <InputError message={errors.title} className="mt-2"/>
//                         </div>
//                         <div>
//                             <InputLabel htmlFor="ISBN_10" value="ISBN 10 BUT LATER WILL BE SSN"
//                                         className="block text-sm font-medium leading-6 text-gray-900"/>
//                             <div className="mt-2">
//                                 <TextInput
//                                     id="ISBN_10"
//                                     name="ISBN_10"
//                                     type={'text'}
//                                     placeholder={'Enter your social security number'}
//                                     value={data.ISBN_10}
//                                     className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
//                                     autoComplete="ISBN_10"
//                                     isFocused={true}
//                                     onChange={(e) => setData('ISBN_10', e.target.value)}
//                                 />
//                             </div>
//                             <InputError message={errors.ISBN_10} className="mt-2"/>
//                         </div>
//                         <div>
//                             <InputLabel htmlFor="ISBN_13" value="ISBN 13- IRAF trust account number"
//                                         className="block text-sm font-medium leading-6 text-gray-900"/>
//                             <div className="mt-2">
//                                 <TextInput
//                                     id="ISBN_13"
//                                     name="ISBN_13"
//                                     type={'text'}
//                                     placeholder={'Enter your IRAF trust account number'}
//                                     value={data.ISBN_13}
//                                     className="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
//                                     autoComplete="ISBN_13"
//                                     isFocused={true}
//                                     onChange={(e) => setData('ISBN_13', e.target.value)}
//                                 />
//                             </div>
//                             <InputError message={errors.ISBN_13} className="mt-2"/>
//                         </div>

//                         {/* CHANGE THIS SECTION FOR SELECTION OF IRA ACCOUNT TYPE */}
//                         <div>
//                             <InputLabel htmlFor="author" value="WHAT TYPE OF IRA ACCOUNT DO YOU WISH TO TRANSFER?"
//                                         className="block text-sm font-medium leading-6 text-gray-900"/>
//                             <div className="mt-2">
//                                 <input type="radio" id="traditional" name="accountType" value="traditional"/>
//                                 <label htmlFor="traditional">Traditional  </label>
//                                 <input type="radio" id="roth" name="accountType" value="roth"/>
//                                 <label htmlFor="roth">Roth  </label>
//                                 <input type="radio" id="sep" name="accountType" value="sep"/>
//                                 <label htmlFor="sep">SEP  </label>
//                                 <input type="radio" id="simple" name="accountType" value="simple"/>
//                                 <label htmlFor="simple">Simple  </label>
//                                 <input type="radio" id="other" name="accountType" value="other"/>
//                                 <label htmlFor="other">Other  </label>

//                             </div>
//                             <InputLabel htmlFor="author" value="IS THE ACCOUNT INHERITED?"
//                                         className="block text-sm font-medium leading-6 text-gray-900"/>
//                             <div className="mt-2">
//                                 <input type="radio" id="traditional" name="accountType" value="traditional"/>
//                                 <label htmlFor="traditional">YES  </label>
//                                 <input type="radio" id="roth" name="accountType" value="roth"/>
//                                 <label htmlFor="roth">NO  </label>
//                             </div>
//                             <InputError message={errors.author} className="mt-2"/>
//                         </div>
//                         {/* CHANGE THIS SECTION FOR SELECTION OF IRA ACCOUNT TYPE */}


//                     </div>
//                     <div className="flex items-center justify-end align-middle gap-2 pt-3 border-t">
//                         <Link
//                             href={route('dashboard.be.books.list')}
//                             className="inline-flex items-center gap-x-1.5 rounded-md bg-gray-600 px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition ease-in-out duration-150"
//                         >
//                             <XCircleIcon className="-mr-0.5 h-5 w-5" aria-hidden="true"/>
//                             Canceltestttttttttt
//                         </Link>

//                         <PrimaryButton type="submit"
//                             className="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
//                             disabled={processing}>
//                             NEXTESTTTTT
//                         </PrimaryButton>
                        // {envelopeId && (
                        //         <div className="mt-4 p-3 border rounded bg-green-50 text-green-800">
                        //             Envelope created successfully! ID: <code>{envelopeId}</code>
                        //         </div>
                        //     )}
//                     </div>
//                 </form>
//             </div>
//         </AuthenticatedLayout>
//     );
// }
