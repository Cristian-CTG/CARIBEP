@extends('system.layouts.app')

@section('content')

    <div class="row">
        <!--<div class="col-lg-6 col-md-12 pt-2 pt-md-0">
            <system-companies-form></system-companies-form>
        </div> -->
        <div class="col-lg-12 col-md-12">
            {{-- <system-certificate-index></system-certificate-index> --}}
            <system-support-configuration></system-support-configuration>
        </div>
    </div>

@endsection
