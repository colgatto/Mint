let taskList = [];
let openTaskIndex = 0;
let ws_task_server = true;
let task_update_on = true;
let firstRender = true;

const statusBadge = {
	init: '<span class="badge badge-light">Init</span>',
	starting: '<span class="badge badge-info">Starting</span>',
	running: '<span class="badge badge-primary">Running</span>',
	success: '<span class="badge badge-success">Success</span>',
	killed: '<span class="badge badge-warning">Killed</span>',
	error: '<span class="badge badge-danger">Error</span>'
};

const progressBg = {
	init: 'bg-light',
	starting: 'bg-info',
	running: 'bg-primary"',
	success: 'bg-success',
	killed: 'bg-warning',
	error: 'bg-danger'
};

const runTask = (task, params={}) => new Promise( (resolve, reject) => {
	$.getJSON('api.php', {
		action: 'run',
		taskName: task,
		params: JSON.stringify(params)
	}, (data) => {
		if(typeof data == 'string') data = JSON.parse(data);
		if(data.error){
			reject(data.error);
		}else{
			resolve(data.response);
		}
	});
});

const askApi = (action, params = {}) => new Promise( (resolve, reject) => {
	params.action = action;
	$.getJSON('api.php', params, (data) => {
		if(typeof data == 'string') data = JSON.parse(data);
		if(data.error){
			reject(data.error);
		}else{
			resolve(data.response);
		}
	});
});

const parseTaskDate = (d) => Date.parse('20'+d.replace(' ','T'));

const createSocket = (i=0) => {
	if(i >= 2){
		console.error('Too many error');
		return;
	}
	ws_task_server = new WebSocket('ws://' + SERVER_IP + ':8087');
	ws_task_server.onerror = function(e) {
		askApi('startMonitorServer').then(()=>{
			setTimeout(()=>{
				createSocket(i+1);
			},1000);
		}).catch(console.error);
	};
	ws_task_server.onopen = function(e) {
		console.log("Connection established!");
	};
	ws_task_server.onmessage = function(e) {
		taskList = JSON.parse(atob(e.data));
		renderTaskList();
		if(firstRender){
			$('#manager .spinner').addClass('hidden');
			$('#manager .row.hidden').removeClass('hidden');
			$('.li_task:first-child').click();
			firstRender = false;
		}
	};
};

const skullIcon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="skull" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-skull fa-w-16 fa-3x"><path fill="currentColor" d="M256 0C114.6 0 0 100.3 0 224c0 70.1 36.9 132.6 94.5 173.7 9.6 6.9 15.2 18.1 13.5 29.9l-9.4 66.2c-1.4 9.6 6 18.2 15.7 18.2H192v-56c0-4.4 3.6-8 8-8h16c4.4 0 8 3.6 8 8v56h64v-56c0-4.4 3.6-8 8-8h16c4.4 0 8 3.6 8 8v56h77.7c9.7 0 17.1-8.6 15.7-18.2l-9.4-66.2c-1.7-11.7 3.8-23 13.5-29.9C475.1 356.6 512 294.1 512 224 512 100.3 397.4 0 256 0zm-96 320c-35.3 0-64-28.7-64-64s28.7-64 64-64 64 28.7 64 64-28.7 64-64 64zm192 0c-35.3 0-64-28.7-64-64s28.7-64 64-64 64 28.7 64 64-28.7 64-64 64z" class=""></path></svg>';
const timesIcon = '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="times" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512" class="svg-inline--fa fa-times fa-w-11 fa-3x"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z" class=""></path></svg>';

const renderDelButton = (task, icon = false) => {
	if(task.status == 'running')
		return '<a class="btn btn-danger float-right btn_del_task" data-action="kill" data-id="' + task.id + '">'
			+ (icon ? skullIcon : 'Kill')
		+ '</a>';
	else
		return '<a class="btn btn-danger float-right btn_del_task" data-action="remove" data-id="' + task.id + '">'
			+ (icon ? timesIcon : 'Remove')
		+ '</a>';
};

const renderTaskProgress = (task, animate = false) => {
	if(task.maxProgress == 0){
		animate = true;
	}
	if(task.status != 'starting' && task.status != 'running'){
		animate = false;
	}
	let moreClass = animate ? 'progress-bar-striped progress-bar-animated' : '';
	return '<div class="progress">'
		+ '<div class="progress-bar ' + moreClass + ' ' + progressBg[task.status] + '"'
			+ ' role="progressbar" style="width: ' + ( task.maxProgress == 0 ? '100' : task.percentage ) + '%"'
			+ ' aria-valuenow="' + task.progress + '" aria-valuemin="0" aria-valuemax="' + task.maxProgress + '">'
			+ ( task.maxProgress <= 1 ? '' : ( task.progress + ' / ' + task.maxProgress ) )
		+ '</div>'
	+ '</div>';
};

const renderTaskList = ()=>{
	let list = [];
	taskList = taskList.sort((a,b) => {
		if( parseTaskDate(a.start_time) > parseTaskDate(b.start_time)) return -1;
		if( parseTaskDate(a.start_time) < parseTaskDate(b.start_time)) return 1;
		return 0;
	});
	for (let i = 0, l = taskList.length; i < l; i++) {
		const task = taskList[i];
		list += '<li id="li_task_' + task.id + '" class="li_task list-group-item" data-index="' + i + '">'
				+ renderDelButton(task, true)
				+ statusBadge[task.status]
				+ '<span class="spn_start_date">' + task.start_time + '</span>'
				+ '<label class="lbl_task_title">[' + task.id + '] ' + task.task_path.slice(task.task_path.lastIndexOf('/')+1) + '</label>'
				+ renderTaskProgress(task)
			+ '</li>';
		if(i == openTaskIndex){
			renderTaskCard();
		}
	}
	$('#monitorList').html(list);
};

const renderTaskCard = () => {
	let task = taskList[openTaskIndex];
	$('#task_data .card-header').html( '[' + task.id + '] ' + task.task_path.slice(task.task_path.lastIndexOf('/') + 1) );
	$('#task_info_path').html('<b>Path: </b><code>' + task.task_path + '</code>');
	$('#task_info_pid').html('<b>PID: </b>' + task.pId);
	$('#task_info_start').html('<b>Start time: </b>' + task.start_time);
	$('#task_info_stop').html('<b>End time: </b>' + task.stop_time);
	$('#task_info_status').html('<b>Status: </b>' + statusBadge[task.status]);
	$('#task_info_uptime').html('<b>Uptime: </b>' + task.uptime);
	let textarea = $('#task_data .card-text')[0];
	let prevData = $('#task_data .card-text').val();
	if(prevData != task.output){
		$('#task_data .card-text').val(task.output);
		textarea.scrollTop = textarea.scrollHeight;
	}
	$('#progress-td').html( renderTaskProgress(task, true) );
	$('#kill-td').html(renderDelButton(task));
};

$(document).on('click', '.li_task', (e) => {
	let t = $(e.currentTarget);
	openTaskIndex = t.data('index');
	renderTaskCard();
	$('#task_data').removeClass('hidden');
});

$(document).on('click', '.btn_del_task', (e) => {
	let t = $(e.currentTarget);
	let id = t.data('id');
	let action = t.data('action');
	if(!confirm(action == 'kill' ? 'Sicuro di voler terminare il Task?' : 'Sicuro di voler rimuovere il Task?')) return;
	askApi(action, { id }).then(()=>{
		if(action == 'remove'){
			$('#task_data').addClass('hidden');
			openTaskIndex = -1;
			$('#task_data .card-header').html('');
			$('#task_info_path').html('');
			$('#task_info_pid').html('');
			$('#task_info_start').html('');
			$('#task_info_stop').html('');
			$('#task_info_status').html('');
			$('#task_info_uptime').html('');
			$('#progress-td').html('');
			$('#kill-td').html('');
			$('#task_data .card-text').val('');
			renderTaskList();
		}else{
			renderTaskList();
		}
	}).catch(console.error);
	//TODO
});