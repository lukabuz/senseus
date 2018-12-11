<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Signature;

class MainController extends Controller
{
    //
    public $georgianMessages = [
        'firstName.required' => 'გთხოვთ შეიყვანოთ სახელი.',
        'firstName.min' => 'სახელი უნდა იყოს მინიმუმ 1 ასო.',
        'firstName.max' => 'სახელი უნდა იყოს მაქსიმუმ 50 ასო.',
        'lastName.required' => 'გთხოვთ შეიყვანოთ გვარი.',
        'lastName.min' => 'გვარი უნდა იყოს მინიმუმ 1 ასო.',
        'lastName.max' => 'გვარი უნდა იყოს მაქსიმუმ 50 ასო.',
        'email.required' => 'გთხოვთ შეიყვანოთ მეილი.',
        'email.email' => 'მეილის ფორმატი არასწორია.'
    ];

    public $englishMessages = [
        'firstName.required' => 'Please enter your name.',
        'firstName.min' => 'Name must be at least 1 character.',
        'firstName.max' => 'Name must be a maximum of 50 characters.',
        'lastName.required' => 'Please enter your last name.',
        'lastName.min' => 'Last name must be at least 1 character.',
        'lastName.max' => 'Last name must be a maximum of 50 characters.',
        'email.required' => 'Please enter your email.',
        'email.email' => 'Please enter a valid email.'
    ];

    public function getStatus(){
        return response()->json([
            'status' => 'success',
            'data' => [
                'signatureCount' => Signature::where('verificationToken', null)->count(),
                'signatures' => Signature::where('verificationToken', null)->where('showInfo', true)
            ]
        ]);
    }

    public function signWithEmail(Request $request){
        if($request->input('language') == 'en'){
            $messages = $this->englishMessages;
        } else {
            $messages = $this->georgianMessages;
        }

        $validatedData = $request->validate([
            'firstName' => 'required|min:1|max:50',
            'lastName' => 'required|min:1|max:50',
            'email' => 'required|email'
        ], 
        $messages
        );

        
    }
}
