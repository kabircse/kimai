<?php

include('../ki_expenses/private_db_layer_'.$kga['server_conn'].'.php');


/**
 * Sum up expenses for the project.
 */
function calculate_expenses_sum($projectId) {
  $expSum = 0;
  $exp_arr = get_arr_exp(0,time(),null,null,array($projectId));

  foreach ($exp_arr as $exp) {
    $expSum += $exp['exp_value'];
  }

  return $expSum;
}

/**
 * Create an array of arrays which hold the size of the pie chart elements
 * for every projects.
 * The first element in the inner arrays represents the unused budget costs,
 * the second element in the inner arrays represents the expense costs,
 * the third and all other elements in the inner arrays represents the
 * costs for individual events.
 * 
 * An visual example for two projects with the ID 2 and 5:
 * $array = {
 *   2 => array (budget left , expenses cost, task1, task2 ),
 *   5 => array (budget left , expenses cost, task1, task2 ),
 * };
 * 
 * @param array $projects IDs of all projects to include in the plot data
 * @param array $usedEvents array of all used events (each as an array of its data)
 * @return array containing arrays for every project which hold the size of the pie chart elements
 * 
 */
function budget_plot_data($projects,&$usedEvents) {

$wages = array();
$eventUsage = array(); // track what events are used
$usedEvents = array(); 


$events = get_arr_evt("all");

 /*
  * sum up expenses
  */
foreach ($projects as $project) {
  
  $pctId = $project['pct_ID'];
  $wages[$pctId]['budget']   = $project['pct_budget'];
  $wages[$pctId]['expenses'] =
      calculate_expenses_sum($project['pct_ID']);

  if ($wages[$pctId]['budget'] < 0) {
    //Costs over budget, set remaining budget to 0.
    $wages[$pctId]['budget'] = 0;
  }

  // initialize entries for every event using its ID
  foreach($events as $event) {
    $wages[$pctId][$event['evt_ID']] = 0;
  }

}


/*
 * sum up wages for every project and every event
 */
foreach ($projects as $project) {
  $projectId = $project['pct_ID'];
  $zef_arr = get_arr_zef(0,time(),null,null,array($projectId));

  foreach ($zef_arr as $zef) {
    $pctId = $zef['zef_pctID'];

    if ($zef['wage_decimal'] == 0.00)
      continue;

    if (key_exists($zef['zef_evtID'],$wages[$pctId])) {
      $eventUsage[$zef['zef_evtID']] = true;
      $wages[$pctId][$zef['zef_evtID']] += $zef['wage_decimal'];
      $wages[$pctId]['budget']          -= $zef['wage_decimal'];
    }

    if ($wages[$pctId]['budget'] < 0) {
      //Costs over budget, set remaining budget to 0.
      $wages[$pctId]['budget'] = 0;
    }
  }
}

/*
 * Delete unused events.
 */
foreach ($events as $event) {
  if (isset($eventUsage[$event['evt_ID']])) {
    $usedEvents[] = $event;
    continue;
  }

  foreach ($wages as $projectData) {
    unset($projectData[$event['evt_ID']]);
  }
}



/* 
 * convert array to javascript array
 */
$plot_data = array();
foreach ($wages as $project_id => $wage_array) {
  $plot_data[$project_id] = '['.implode(',',$wage_array).']';
}
return $plot_data;
}


?>