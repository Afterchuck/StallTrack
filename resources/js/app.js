//
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="mobile_number"]').forEach((input) => {
        const keepDigitsOnly = () => {
            input.value = input.value.replace(/\D/g, '').slice(0, 10);
        };

        input.addEventListener('input', keepDigitsOnly);
        keepDigitsOnly();
    });

    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);

            if (!input) {
                return;
            }

            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Hide' : 'Show';
            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(isHidden));
        });
    });

    const receiptDialog = document.getElementById('payment-receipt-dialog');

    if (receiptDialog) {
        let currentReceipt = null;
        const receiptFields = {
            'receipt-reference': 'receiptNumber',
            'receipt-vendor': 'vendorName',
            'receipt-bill': 'billNumber',
            'receipt-stall': 'stallNumber',
            'receipt-method': 'paymentMethod',
            'receipt-date': 'confirmedDate',
            'receipt-start': 'billingStart',
            'receipt-end': 'billingEnd',
        };

        document.querySelectorAll('[data-open-receipt]').forEach((button) => {
            button.addEventListener('click', () => {
                currentReceipt = button.dataset;
                document.getElementById('receipt-amount').textContent = `₱${currentReceipt.amount}`;

                Object.entries(receiptFields).forEach(([elementId, property]) => {
                    document.getElementById(elementId).textContent = currentReceipt[property];
                });

                receiptDialog.showModal();
            });
        });

        document.getElementById('close-payment-receipt').addEventListener('click', () => receiptDialog.close());
        receiptDialog.addEventListener('click', (event) => {
            if (event.target === receiptDialog) {
                receiptDialog.close();
            }
        });

        document.getElementById('download-payment-receipt').addEventListener('click', () => {
            if (!currentReceipt) {
                return;
            }

            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            if (!context) {
                return;
            }

            canvas.width = 1000;
            canvas.height = 1370;
            context.fillStyle = '#f7f9fc';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.fillStyle = '#ffffff';
            context.strokeStyle = '#dce4ef';
            context.lineWidth = 3;
            context.beginPath();
            context.roundRect(40, 40, 920, 1290, 42);
            context.fill();
            context.stroke();

            context.textAlign = 'center';
            context.fillStyle = '#08295e';
            context.font = '800 68px Arial, sans-serif';
            context.fillText('StallTrack', 500, 160);
            context.fillStyle = '#94a3b8';
            context.font = '700 28px Arial, sans-serif';
            context.fillText('PUBLIC MARKET', 500, 215);
            context.font = '800 27px Arial, sans-serif';
            context.fillText('VENDOR PAYMENT RECEIPT', 500, 280);
            context.fillStyle = '#08295e';
            context.font = '800 64px Arial, sans-serif';
            context.fillText(`₱${currentReceipt.amount}`, 500, 385);

            const details = [
                ['REFERENCE ID', currentReceipt.receiptNumber],
                ['VENDOR', currentReceipt.vendorName],
                ['BILL', currentReceipt.billNumber],
                ['STALL', currentReceipt.stallNumber],
                ['PAYMENT METHOD', currentReceipt.paymentMethod],
                ['PAID DATE', currentReceipt.confirmedDate],
                ['BILLING START', currentReceipt.billingStart],
                ['BILLING END', currentReceipt.billingEnd],
                ['PAYMENT STATUS', 'Confirmed'],
            ];
            const labelX = 95;
            const valueX = 905;
            const firstRowY = 500;
            const rowHeight = 82;

            details.forEach(([label, value], index) => {
                const rowY = firstRowY + index * rowHeight;

                if (index === 6) {
                    context.strokeStyle = '#dce4ef';
                    context.lineWidth = 2;
                    context.beginPath();
                    context.moveTo(labelX, rowY - 38);
                    context.lineTo(valueX, rowY - 38);
                    context.stroke();
                }

                context.textAlign = 'left';
                context.fillStyle = '#64748b';
                context.font = '800 21px Arial, sans-serif';
                context.fillText(label, labelX, rowY);
                context.textAlign = 'right';
                context.fillStyle = '#344256';
                let valueFontSize = 26;

                while (valueFontSize > 17) {
                    context.font = `800 ${valueFontSize}px Arial, sans-serif`;
                    if (context.measureText(value).width <= 490) {
                        break;
                    }
                    valueFontSize -= 1;
                }

                context.fillText(value, valueX, rowY);
            });

            context.textAlign = 'center';
            context.fillStyle = '#94a3b8';
            context.font = '700 20px Arial, sans-serif';
            context.fillText('Generated from StallTrack vendor billing history', 500, 1260);

            const downloadLink = document.createElement('a');
            const safeReceiptNumber = currentReceipt.receiptNumber.replace(/[^a-zA-Z0-9_-]/g, '-');
            downloadLink.download = `stalltrack-receipt-${safeReceiptNumber}.png`;
            downloadLink.href = canvas.toDataURL('image/png');
            downloadLink.click();
        });
    }
});
