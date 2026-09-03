/**
 * Endpoint doPost para Arc Task App.
 * Añade este archivo al proyecto y despliega la Web App.
 * Reutiliza las funciones existentes de Code.gs.
 */

function doPost(e) {
  const body = JSON.parse(e.postData.contents || '{}');

  if (body.api_key !== PropertiesService.getScriptProperties().getProperty('API_KEY')) {
    return jsonResponse_({ success: false, error: 'API key inválida' });
  }

  const action = body.action;

  switch (action) {
    case 'get_tasks':
      return jsonResponse_(getTasksFromWP_(body));
    case 'create_task':
      return jsonResponse_(createTaskFromWP_(body));
    case 'update_task':
      return jsonResponse_(updateTaskFromWP_(body));
    case 'get_stats':
      return jsonResponse_(getStatsFromWP_(body));
    default:
      return jsonResponse_({ success: false, error: 'Acción no soportada' });
  }
}

function jsonResponse_(payload) {
  return ContentService.createTextOutput(JSON.stringify(payload))
    .setMimeType(ContentService.MimeType.JSON);
}

function getTasksFromWP_(data) {
  const status = data.status || 'all';
  const projects = getProjects();
  const projectNames = {};
  projects.forEach(function(p) { projectNames[String(p.ID)] = p.Name || ''; });

  let tasks = getTasks().map(function(t) {
    return {
      ID: t.ID,
      ProjectID: t.ProjectID,
      ProjectName: projectNames[String(t.ProjectID)] || '',
      Title: t.Title,
      Description: t.Description,
      Status: t.Status,
      Priority: t.Priority,
      Assignee: t.Assignee,
      DueDate: t.DueDate
    };
  });

  if (status !== 'all') {
    tasks = tasks.filter(function(t) { return String(t.Status) === status; });
  }

  return { success: true, tasks: tasks };
}

function createTaskFromWP_(data) {
  const taskData = {
    projectId: data.projectId,
    title: data.title,
    description: data.description,
    status: data.status,
    priority: data.priority,
    assignee: data.assignee,
    dueDate: data.dueDate
  };
  const created = createTask(taskData);
  return { success: true, message: 'Tarea creada', tasks: created };
}

function updateTaskFromWP_(data) {
  const taskData = {};
  if (data.title !== undefined) taskData.title = data.title;
  if (data.description !== undefined) taskData.description = data.description;
  if (data.status !== undefined) taskData.status = data.status;
  if (data.priority !== undefined) taskData.priority = data.priority;
  if (data.assignee !== undefined) taskData.assignee = data.assignee;
  if (data.dueDate !== undefined) taskData.dueDate = data.dueDate;
  if (data.projectId !== undefined) taskData.projectId = data.projectId;

  const updated = updateTask(data.taskId, taskData);
  return { success: true, message: 'Tarea actualizada', tasks: updated };
}

function getStatsFromWP_(data) {
  const tasks = getTasks();
  const active = tasks.filter(function(t) { return String(t.Status) !== 'Done'; }).length;
  return {
    success: true,
    week_hours: 0,
    eod_count: 0,
    active_tasks: active,
    candidates: 0
  };
}
