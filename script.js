//Navigation

const expenseContainer = document.getElementById('expense-container');
const dashboardContainer = document.getElementById('dashboard-content');
const annualContainer = document.getElementById('annual-container');
const settingsContainer = document.getElementById('settings-container');
const historyButton = document.getElementById('history');
const dashboardButton = document.getElementById('dashboard');
const settingsButton = document.getElementById('settings');
const h3History = document.getElementById('h3-history');
const h3Dashboard = document.getElementById('h3-dashboard');

historyButton.addEventListener('click', function(event) {

    expenseContainer.style.display = 'block';
    dashboardContainer.style.display = 'none';
    annualContainer.style.display = 'none';
    settingsContainer.style.display = 'none';
    h3Dashboard.style.display = 'none';

    document.title = 'History';

})

dashboardButton.addEventListener('click', function(event) {

    expenseContainer.style.display = 'none';
    dashboardContainer.style.display = 'block';
    annualContainer.style.display = 'block';
    settingsContainer.style.display = 'none';

    document.title = 'Dashboard';

})

settingsButton.addEventListener('click', function(event) {

    expenseContainer.style.display = 'none';
    dashboardContainer.style.display = 'none';
    annualContainer.style.display = 'none';
    settingsContainer.style.display = 'block';

    document.title = 'Settings';
})



