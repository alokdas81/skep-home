
@extends('layouts.admin')

@section('content')

 <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>Dashboard</h1>
    </section>

    <!-- Main content -->

    <section class="content">
      <div class="row">
        <div class="col-md-6 col-sm-12 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-teal"><i class="fa fa-group"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total Cleaners</span>
              <span class="info-box-number"><small>{{ $cleaner_count }}</small></span>
            </div>
          </div>
        </div>

        <div class="col-md-6 col-sm-12 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-teal"><i class="fa fa-user"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total Homowners</span>
              <span class="info-box-number">{{ $homeowner }}</small></span>
            </div>
            <!-- /.info-box-content -->
          </div>
          <!-- /.info-box -->
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 col-sm-12 col-xs-12">
          <div id="container" style="min-width: 500px; height: 380px; max-width: 380px; margin: 0 auto"></div>
        </div>
        <div class="col-md-6 col-sm-12 col-xs-12">
          <div id="contain_values" style="min-width: 500px; height: 380px; max-width: 380px; margin: 0 auto"></div>
        </div>
      </div>
      <div class="row">
          <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="info-box">
              <span class="info-box-icon bg-teal"><i class="fa fa-ticket"></i></span>
                <div class="info-box-content">
                  <span class="info-box-text">Total Tickets This Month</span>
                  <span class="info-box-number">{{ $tickets_count }}</small></span>
                </div>
            </div>
          </div>
        <div class="col-md-6 col-sm-12 col-xs-12">
          <div class="info-box">
            <span class="info-box-icon bg-teal"><i class="fa fa-envelope-open-o"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Number Of Open Tickets</span>
              <span class="info-box-number"><small>{{ $open_tickets_count }}</small></span>
            </div>
          </div>
        </div>
      </div>
      <!-- /.row -->
    </section>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

    <script type="text/javascript">
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Task', 'No of Active Cleaners'],
          ['Active Cleaners',     <?php echo $approved_cleaner_count;?>],
          ['Not Active Cleaners', <?php echo $unapproved_cleaners_count;?>],
        ]);

        var options = {
          title: 'Active Cleaners',
          is3D: true,
        };

        var chart = new google.visualization.PieChart(document.getElementById('container'));
        chart.draw(data, options);
      }

      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChart1);
      function drawChart1() {
        var data = google.visualization.arrayToDataTable([
          ['Task', 'No of Active Homeowners'],
          ['Active Homeowners',     <?php echo $approved_homeowner_count;?>],
          ['Not Active Homeowners',  <?php echo $unapproved_homeowners_count;?>],
        ]);

        var options = {
          title: 'Active Homeowners',
          is3D: true,
        };

        var chart = new google.visualization.PieChart(document.getElementById('contain_values'));
        chart.draw(data, options);
      }    
</script>
@endsection
