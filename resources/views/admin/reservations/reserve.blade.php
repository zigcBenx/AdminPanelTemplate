@extends('layouts.admin')
@section('content')
<div class="content">
    <div class="row">
        <div class="col-lg-12">
        	<div class="container">
                <div class="termini">

                    <div class="form-group">

                        <label for="datepicker">Date range:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
						<span class="input-group-text">
							<i class="far fa-calendar-alt"></i>
						</span>
                            </div>
                            <!--<input type="text" class="form-control float-right date" id="datepicker">-->
                            <input class="form-control" id="date" name="date" placeholder="MM/DD/YYY" type="text" />
                        </div>
                        <small>Če je datum prazen, se pokažejo vsi prosti termini.</small>
                        <br>
                        <br>
                        <label for="users-doctors-list">Vaši zdravniki</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
						<span class="input-group-text">
							<i class="far fa-hospital"></i>
						</span>
                            </div>
                            <select name="" id="users-doctors-list" class="form-control float-right" >

                            </select>
                            <a href="/admin" class="btn btn-success">Dodaj zdravnika <i class="fa fa-plus"></i></a>
                        </div>
                        <!-- /.input group -->
                    </div>

                    <hr>
                    <p id="no-termini" style="display:none;">Ni možnih rezervacij za ta termin.</p>

                    <div id="free-termini">
                        <div class="loading" style="position:absolute; left:50%; top:0px;">
                            <i class="fa fa-spinner" style="font-size: 5em;"></i>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection


@section('scripts')
@parent

<script>
    $(document).ready(function () {
        // For initial display of termini
        let displayDoctor = null;

        $.post('{{ route("admin.user-doctor-show") }}', {_token: "{{ csrf_token() }}"})
            .done( function(data) {
                $('#users-doctors-list').html('');
                displayDoctor = data[0].doctor_id;

                for(let i = 0; i < data.length; i++) {
                    let doctorId = data[i].doctor_id;
                    fetch('https://enarocanje-gw1.comtrade.com/ctNarocanjeTest/api/ElektronskoNarocanje/GetDoctorInfo?request.doctorIVZCode='+doctorId+'&request.providerZZZSNumber=102320&request.client.uniqueDeviceId=A3DE534DB&request.client.clientType= browser (User-Agent): Mozilla/5.0&request.client.applicationVersion=1.22&request.client.applicationId=myXlife')
                        .then( res => res.json())
                        .then( res => {
                            let doctorsFirstName = res.DoctorInfos[0].DoctorFirstName;
                            let doctorsLastName = res.DoctorInfos[0].DoctorLastName;
                            $("#users-doctors-list").append('<option value="'+res.DoctorInfos[0].DoctorIVZCode+'">'+doctorsFirstName+' '+doctorsLastName+'</option>')
                        });
                }

                showFreeSlots(displayDoctor);

            });

        var date_input=$('input[name="date"]');
        date_input.datepicker({format:'d.m.yyyy'});

        date_input.datepicker().on('changeDate', function (ev) {
            showFreeSlots($("#users-doctors-list").val(),$(this).val());
        });

        $("#users-doctors-list").change(function(){
            showFreeSlots($(this).val(),$('input[name="date"]').val());
        });


        function showFreeSlots(docId, selectedDate=null) {
            $('#loading').show();
            $.post('{{route('admin.get-work-place')}}', {_token: "{{ csrf_token() }}", docId:docId})
                .done(function(data){
                    let workplaceOfselectedDoctor = data[0].workspace;
                    fetch('https://enarocanje-gw1.comtrade.com/ctNarocanjeTest/api/ElektronskoNarocanje/GetFreeSlots?request.workplaceCode='+workplaceOfselectedDoctor+'&request.doctorIVZCode='+docId+'&request.providerZZZSNumber=102320&request.client.uniqueDeviceId=A3DE534DB&request.client.clientType=browser (User-Agent): Mozilla/5.0&request.client.applicationVersion=1.22&request.client.applicationId=myXlife')
                        .then( res => res.json())
                        .then( res => {

                            if(!res.IsSuccessful){
                                // alert("API error");
                                // console.log(res);
                                return;
                            }
                            if(!res.FreeSlots.length){
                                $("#no-termini").show();
                                $("#free-termini").html('');
                            }else{
                                $("#no-termini").hide();
                                $("#free-termini").html('');

                                for (let i = 0; i < res.FreeSlots.length; i++) {
                                    // console.log(moment(selectedDate,'D.M.Y').format('D.M.Y'));
                                    // console.log(moment(res.FreeSlots[i].Start).format('D.M.Y'));
                                    if(selectedDate == "" || (moment(selectedDate,'D.M.Y').format('D.M.Y') == moment(res.FreeSlots[i].Start).format('D.M.Y'))) {
                                        $("#free-termini").append(
                                            '<div class="callout callout-info">\n' +
                                            '                        <div class="row">\n' +
                                            '                            <div class="col-4">\n' +
                                            '                                <h5>' + $("#users-doctors-list option:selected").text() + '</h5>\n' +
                                            '                            </div>\n' +
                                            '                            <div class="col-4">\n' +
                                            '                                <p style="font-size:30px;">' + moment(res.FreeSlots[i].Start).format('H:mm') + " - " + moment(res.FreeSlots[i].End).format('H:mm') + '</p>' +
                                            '                            </div>\n' +
                                            '                            <div class="col-4">\n' +
                                            '                                <button type="button" class="btn btn-info btn-lg" style="float:right;">Naroči se</button>\n' +
                                            '                            </div>\n' +
                                            '                        </div>' +
                                            '                        <p>' + moment(res.FreeSlots[i].End).format('D.M.Y') + '</p>' +
                                            '                    </div>');
                                        $("#no-termini").hide();
                                    }else{
                                        $("#no-termini").show();
                                    }
                                }
                                $('#loading').hide();
                            }

                        });
                });
        }
    });
</script>
@endsection
