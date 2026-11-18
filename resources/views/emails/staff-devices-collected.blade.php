<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #222;">
    <p>Dear {{ $employee->name }},</p>

    <p>
        You have collected the UNBS device <strong>{{ $collected->label() }}</strong>
        on {{ $collected->collected_at->format('d M Y \a\t H:i') }}.
    </p>

    <p>These are all the devices registered to you at UNBS:</p>

    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; border-color: #ccc;">
        <tr style="background: #f2f2f2;">
            <th align="left">Owner</th>
            <th align="left">Type</th>
            <th align="left">Make / model</th>
            <th align="left">Serial number</th>
            <th align="left">Sticker code</th>
        </tr>
        @foreach ($devices as $device)
            <tr>
                <td>{{ $device->owner_type === 'organization' ? 'UNBS' : 'Personal' }}</td>
                <td>{{ $device->device_type }}</td>
                <td>{{ $device->make_model }}</td>
                <td>{{ $device->serial_number }}</td>
                <td>{{ $device->sticker_code }}</td>
            </tr>
        @endforeach
    </table>

    <p>
        Please check out each device at the Entry checkpoint when you leave the premises.
        If anything on this list is wrong, contact the Registration Officer.
    </p>

    <p>UNBS Visitor Management System</p>
</body>
</html>