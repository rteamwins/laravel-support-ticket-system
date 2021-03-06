<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\TicketFormRequest;

use App\Ticket;

use Illuminate\Support\Facades\Mail;

class TicketsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tickets = Ticket::all();
        /* compact() method converts the result to an array, and passes it to the view. */
        return view('tickets.index', compact('tickets'));
        // OR:
        //return view('tickets.index')->with('tickets', $tickets);
        // OR:
        // return view('tickets.index', ['tickets'=> $tickets]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tickets.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TicketFormRequest $request)
    {
        // return $request->all();
        /* We use the uniqid() function to generate a unique ID based on the microtime. You may use md5() function to generate the slugs or create your custom slugs. */
        $slug = uniqid();
        $ticket = new Ticket(array(
            'title' => $request->get('title'),
            'content' => $request->get('content'),
            'slug' => $slug
        ));
        // save the data to our database.
        $ticket->save();

        // send email to admin that a new ticket was submitted by a user.
        $data = array(
            'ticket' => $slug,
        );
        Mail::send('emails.ticket', $data, function($message) {
            $message->from('phpsitescripts@outlook.com', 'Laravel Support Ticket System!');
            $message->to('sabrina@phpsitescripts.com')->subject('There is a new support ticket!');
        });

        return redirect('/tickets')->with('status', 'Your ticket has been created! Its unique id is: ' . $slug);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        // The firstOrFail method will retrieve the first result of the query. If there is no result, it will throw a ModelNotFoundException (looks nice still). If you don't want to throw an exception, you can use the first() method.
        $ticket = Ticket::whereSlug($slug)->firstOrFail();

        /* get all the comments for this ticket. comments() method is in the Ticket model to
        indicate that Tickets hasMany comments */
        $comments = $ticket->comments()->get();
        return view('tickets.show', compact('ticket', 'comments'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function edit($slug)
    {
        $ticket = Ticket::whereSlug($slug)->firstOrFail();
        return view('tickets.edit', compact('ticket'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $ticket = Ticket::whereSlug($slug)->firstOrFail();
        $ticket->title = $request->get('title');
        $ticket->content = $request->get('content');
        if ($request->get('status') != null) {
            $ticket->status = 0;
        } else {
            $ticket->status = 1;
        }
        $ticket->save();
        return redirect(action('TicketsController@index', $ticket->slug))->with('status', 'The ticket ' . $slug . ' has been updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        $ticket = Ticket::whereSlug($slug)->firstOrFail();
        $ticket->delete();
        return redirect('/tickets')->with('status', 'The ticket ' . $slug . ' has been deleted!');
    }
}
