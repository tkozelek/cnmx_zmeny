import 'flowbite';
import DateRangePicker from 'flowbite-datepicker/DateRangePicker';
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
    // call the ajax function
    addButton.click(function() {
        let clickedButton = $(this);
        let day = clickedButton.data('day');
        let popis = $('#extra_popis').val();
        ajaxRequest(addUserUrl, 'POST', { day: day, popis: popis }, function(response) {
            if (response['error'] === 10) {
                return;
            }

            if (response['status'] === 2) {
                clickedButton.removeClass('bg-green-300 hover:bg-green-600').addClass('bg-red-300 hover:bg-red-400').text('ZAPISAŤ');
                showToast("Deň odpísaný.", "error");
            } else {
                clickedButton.removeClass('bg-red-300 hover:bg-red-400').addClass('bg-green-300 hover:bg-green-600').text('ODPISAŤ');
                showToast("Deň zapísaný.", "success");
            }

            let usersContainer = $('#c-' + day).find('.users-container');
            usersContainer.empty(); // Clear existing users
            response['users'].forEach(function (user) {
                let isBlocked = user.id_role === 4;

                let userDiv = $('<div>').addClass('bg-gray-900 text-white border-b border-gray-700 py-1 shadow-md text-md rows px-1 flex items-center');

                if (isBlocked) {
                    userDiv.addClass('line-through justify-center');
                }  else {
                    userDiv.addClass('justify-center');
                }

                let popisHtml = user.pivot.popis ? `<span class="text-slate-400/80 italic text-sm ml-1">(${user.pivot.popis})</span>` : '';
                let userNameHtml = `${user.lastname} ${user.name[0]}.`;

                let nameWrapper = $('<span class="truncate max-w-[calc(100%-20px)]">').html(userNameHtml + popisHtml);
                userDiv.append(nameWrapper);

                usersContainer.append(userDiv);
            });
        }, function(xhr, status, error) {
            console.log("error");
            console.error(xhr.responseText);
        });
    });
});

function showToast(message, type = 'success', icon = '') {
    window.dispatchEvent(new CustomEvent('toast', {
        detail: { message, type, icon }
    }));
}

$(document).ready(function() {
    $('#names_checkbox').change(function() {
        $(".rows").toggle();
    });
});


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
                if (response['status'] === 200) {
                    showToast("Súbor zmazaný.", "warning");
                }
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

document.addEventListener('DOMContentLoaded', function() {
    Chart.register(...registerables);

    var canvas = document.getElementById('barChart');
    if (canvas) {
        var ctx = canvas.getContext('2d');

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
                },
                responsive: true
            }
        });
    }
});

