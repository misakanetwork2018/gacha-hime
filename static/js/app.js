$(function () {
    $(document).on('click', '.dialog-close', close_dialog)
});

$.ajaxSetup({
    complete: function (XMLHttpRequest,status) {
        if(status === 'error' || status === 'parsererror'){
            window.location.reload()
        }
    },
})

function gacha() {
    $(".gacha").addClass('disabled').text("抽奖中...").attr("disabled", true);
    $.ajax({
        url: '/index/gacha',
        method: 'post',
        cache: false,
        dataType: 'json',
        success: function (data) {
            runningAnimate(0, data.gacha.total, data.gacha.times, function () {
                $(".gacha").removeClass('disabled').text("点击抽奖").attr("disabled", false)
                dialog('恭喜中奖', '您中的是：' + data.result.name + "<br>" + data.msg)
                $('#result-table').prepend('<tr>' +
                    '<td>' + data.result.id + '</td>' +
                    '<td>' + data.result.name + '</td>' +
                    '<td>' + data.result.status + '</td>' +
                    '<td>' + data.result.description + '</td>' +
                    '<td>' + data.result.created + '</td>' +
                    '<td>-</td>' +
                    '</tr>');
                $('#gacha-times').text(data.gacha.rest)
            })
        },
        error: function () {

        }
    })
}

function runningAnimate(offset, total, times, done) {
    var count = 0;

    function activeNextItem() {
        var items = $(".gacha-pool").children(".item")
        var item_offset = (offset + count) % total
        var select = items.get(item_offset)
        var speed = 50

        items.removeClass("active")
        $(select).addClass("active")

        var percent = count / times

        if (percent < 0.05) {
            speed = 200
        } else if (percent < 0.1) {
            speed = 100
        } else if (percent >= 0.1 && percent < 0.2) {
            speed = 50
        } else if (percent >= 0.7 && percent < 0.8) {
            speed = 100
        } else if (percent >= 0.8 && percent < 0.85) {
            speed = 200
        } else if (percent >= 0.85 && percent < 0.9) {
            speed = 300
        } else if (percent >= 0.9) {
            speed = 400
        }

        if (count++ < times) {
            setTimeout(activeNextItem, speed)
        } else {
            setTimeout(done, 500)
        }
    }

    setTimeout(activeNextItem, 200)
}

function randomNum(minNum, maxNum) {
    switch (arguments.length) {
        case 1:
            return parseInt(Math.random() * minNum + 1, 10);
        case 2:
            return parseInt(Math.random() * (maxNum - minNum + 1) + minNum, 10);
        default:
            return 0;
    }
}

function dialog(title, content, buttons) {
    var dialog_shape = $('<div class="dialog-shape"></div>');
    var body = $('body');
    dialog_shape.appendTo(body);

    if (buttons === undefined) {
        buttons = [{name: '关闭', onclick: 'close_dialog()', class: 'button-danger'}]
    }

    var button_html = '';

    if (buttons !== null) {
        buttons.forEach(function (item) {
            button_html += '<button class="button ' + item.class + '" onclick="' + item.onclick + '">' +
                item.name + '</button>'
        })
    }

    var dialog = $('<div class="dialog"></div>');
    var dialog_body = $('<div class="dialog-body clearfix"></div>');

    dialog_body.html(content)

    dialog.append($('<div class="dialog-header">' + title + '<span class="dialog-close">×</span></div>'));
    dialog.append(dialog_body);
    dialog.append($('<div class="dialog-footer">' + button_html + '</div>'));
    dialog.appendTo(body);
    $('.dialog').show();
}

function close_dialog() {
    $('.dialog,.dialog-shape').remove()
}

function exchange(id) {
    $.ajax({
        url: '/index/exchangeCheck',
        method: 'post',
        cache: false,
        data: {id: id},
        dataType: 'json',
        success: function (data) {
            if (data.allowed) {
                var form = $('<form action="/index/exchange" method="post" id="exchange-form"></form>')
                var table = $('<table></table>');
                form.append('<input type="hidden" name="id" value="' + id + '">')
                data.field.forEach(function (field) {
                    var tr = $('<tr></tr>');
                    tr.append('<td align="right"><label>' + field.label + '</label></td>');
                    switch (field.type) {
                        case 'select':
                            var td = $('<td></td>');
                            var select = $('<select name="' + field.name + '"' + (field.required ? ' required' : '')
                                + '></select>');
                            if (field.options.length === undefined) {
                                for (var key in field.options) {
                                    select.append('<option value="' + key + '">' + field.options[key] + '</option>')
                                }
                            } else {
                                field.options.forEach(function (value) {
                                    select.append('<option>' + value + '</option>')
                                })
                            }
                            select.appendTo(td)
                            td.appendTo(tr)
                            break;
                        default:
                            tr.append('<td><input type="' + field.type + '" name="' + field.name + '"' +
                                (field.required ? ' required' : '') +
                                (field.value === undefined ? '' : ' value="' + field.value + '"') + '></td>')
                    }
                    tr.appendTo(table)
                })
                table.appendTo(form)
                dialog('兑换奖品', form, [
                    {name: '兑换', onclick: "$('#exchange-form').submit()", class: 'button-primary'},
                    {name: '关闭', onclick: 'close_dialog()', class: 'button-danger'}
                ])
            } else {
                dialog('无法兑换奖品', data.reason)
            }
        },
        error: function () {

        }
    })
}