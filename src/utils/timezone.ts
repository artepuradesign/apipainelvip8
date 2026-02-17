// Fuso horário padrão da aplicação: Brasília (UTC-3)
export const APP_TIMEZONE = 'America/Sao_Paulo';

/**
 * Retorna a data/hora atual no fuso de Brasília
 */
export function nowBrasilia(): Date {
  const now = new Date();
  // Converter para string no fuso de Brasília e parsear de volta
  const brasiliaString = now.toLocaleString('en-US', { timeZone: APP_TIMEZONE });
  return new Date(brasiliaString);
}

/**
 * Formata uma data para exibição no fuso de Brasília
 */
export function formatDateBR(
  dateInput: string | Date | undefined | null,
  options?: {
    showTime?: boolean;
    showSeconds?: boolean;
  }
): string {
  if (!dateInput) return '—';

  try {
    const date = typeof dateInput === 'string' ? new Date(dateInput) : dateInput;
    if (isNaN(date.getTime())) return '—';

    const formatOptions: Intl.DateTimeFormatOptions = {
      timeZone: APP_TIMEZONE,
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    };

    if (options?.showTime) {
      formatOptions.hour = '2-digit';
      formatOptions.minute = '2-digit';
      if (options?.showSeconds) {
        formatOptions.second = '2-digit';
      }
    }

    return date.toLocaleDateString('pt-BR', formatOptions);
  } catch {
    return '—';
  }
}

/**
 * Retorna "hoje" no fuso de Brasília como YYYY-MM-DD
 */
export function todayBrasilia(): string {
  const now = nowBrasilia();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

/**
 * Calcula dias restantes a partir de uma data final, usando o fuso de Brasília
 */
export function remainingDaysBR(endDate: string | Date | undefined | null): number {
  if (!endDate) return 0;
  const end = typeof endDate === 'string' ? new Date(endDate) : endDate;
  if (isNaN(end.getTime())) return 0;

  const today = nowBrasilia();
  today.setHours(0, 0, 0, 0);
  
  const endNormalized = new Date(end);
  endNormalized.setHours(0, 0, 0, 0);

  const diffMs = endNormalized.getTime() - today.getTime();
  return Math.max(0, Math.ceil(diffMs / (1000 * 60 * 60 * 24)));
}
