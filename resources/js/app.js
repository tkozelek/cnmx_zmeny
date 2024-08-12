import 'flowbite';
import { Modal } from 'flowbite';
import 'flowbite/dist/datepicker.js';
import DateRangePicker from 'flowbite-datepicker/DateRangePicker';
import {Datepicker} from "flowbite-datepicker";
import sk from "flowbite-datepicker/locales/sk";
import Dropzone  from "dropzone";
import Sortable from "sortablejs";
import {Chart, registerables} from "chart.js";


$(document).ready(function() {
    function ajaxRequest(url, method, data, successCallback, errorCallback) {
        let token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: url,
            method: method,
            headers: {
                'X-CSRF-TOKEN': token
            },
            data: data,
            success: successCallback,
            error: errorCallback
        });
    }

    let addButton = $('.add-user-btn');
    // Click event handler for adding a user
    addButton.click(function() {
        let clickedButton = $(this);
        let day = clickedButton.data('day');
        let popis = $('#extra_popis').val();
        ajaxRequest(addUserUrl, 'POST', { day: day, popis: popis }, function(response) {
            if (response['error'] === 10) {
                return;
            }

            if (clickedButton.hasClass('bg-green-300')) {
                clickedButton.removeClass('bg-green-300 hover:bg-green-600').addClass('bg-red-300 hover:bg-red-400').text('ZAPISAŤ');
            } else {
                clickedButton.removeClass('bg-red-300 hover:bg-red-400').addClass('bg-green-300 hover:bg-green-600').text('ODPISAŤ');
            }

            let usersContainer = $('#c-' + day).find('.users-container');
            usersContainer.empty(); // Clear existing users
            response['users'].forEach(function(user) {
                let userRow = '';
                let popis;
                if (user.pivot.popis)
                    popis = user.lastname + ' ' + user.name[0] + '. <span style="font-size: smaller;">(' + user.pivot.popis + ')</span>'
                else
                    popis = user.lastname + ' ' + user.name[0] + '.';
                if (!$('#names_checkbox').is(':checked')) {
                    userRow = $('<div class="bg-gray-900 text-white border-b border-gray-700 py-1 shadow-md rows" style="display: none;">')
                        .html(popis);
                } else {
                    if (user.id_role === 4)
                        userRow = $('<div class="bg-gray-900 text-white border-b border-gray-700 py-1 shadow-md rows line-through">')
                            .html(popis);
                    else
                        userRow = $('<div class="bg-gray-900 text-white border-b border-gray-700 py-1 shadow-md rows">')
                            .html(popis);
                }
                usersContainer.append(userRow);
            });
        }, function(xhr, status, error) {
            console.log("error");
            console.error(xhr.responseText);
        });
    });
});


$(document).ready(function() {
    $('#names_checkbox').change(function() {
        $(".rows").toggle();
    });
});

Datepicker.locales.sk = sk.sk;

let options = {
    language: "sk",
    weekStart: "1", // Set the start of the week to Monday
    format: "dd.mm.yyyy", // Set the date format
    todayHighlight: true,
    autoclose: true,
    hideOnClickOutside: true,
    hideOnSelect: true,
    orientation: "up",
};


let dateRangePickerEl = document.getElementById('daterangepicker'); // Get the date range picker element
if (dateRangePickerEl)
    new DateRangePicker(dateRangePickerEl, options); // Initialize the DateRangePicker with the specified options

$(document).ready(function() {
    $('.delete-file').click(function() {
        let token = $('meta[name="csrf-token"]').attr('content');
        var fileId = $(this).data('file-id');

        $.ajax({
            url: '/upload/' + fileId + '/destroy',
            headers: {
                'X-CSRF-TOKEN': token
            },
            type: 'DELETE',
            success: function(response) {
                // Refresh or update file list after successful deletion
                console.log(response.message);
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });

    });
});

document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');

    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            if (type === 'password') {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            } else {
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            }
        });
    }
});


$(document).ready(function () {
    let draggable = document.getElementById('draggable');
    if (draggable)
        var sortable = new Sortable(draggable, {
            group: 'shared',
            animation: 150, // ms, animation speed moving items when sorting
            easing: 'cubic-bezier(1, 0, 0, 1)',
            onEnd: function (evt) {
                // Callback function when sorting ends
                console.log('Dragged element:', evt.item);
            }
        });

    let dropboxes = document.getElementsByClassName('droppable');
    if (dropboxes)
        dropboxes.forEach(createDropbox);

    function createDropbox(item, index) {
        new Sortable(item, {
            group: 'shared',
            animation: 150, // ms, animation speed moving items when sorting
            easing: 'cubic-bezier(1, 0, 0, 1)',
            onEnd: function (evt) {
                console.log('Dropped element:', evt.item);
            }
        })
    }
});

Chart.register(...registerables);
var ctx = document.getElementById('barChart').getContext('2d');
var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota', 'Nedeľa'],
        datasets: [{
            label: 'Počty dni',
            data: chartData,
            backgroundColor: 'rgba(5,19,183,0.2)',
            borderColor: 'rgb(21,26,155)',
            borderWidth: 1
        }]
    },
    options: {
        plugins: {
            title: {
                display: true,
                text: 'Frekvencia zapisovanych dni',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                },
                grace: '5%',
            }
        },
        elements: {
            bar: {
                backgroundColor: '#ea3308'
            }
        }
    }
});


