
import React, { useEffect, useState, useRef } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Loader2, Calendar, Clock, Wallet, User as UserIcon, Mail, CreditCard, Percent, FileText, Save, X } from "lucide-react";
import { getFullApiUrl } from '@/utils/apiHelper';
import type { User } from "@/types/user";
import { format, parseISO } from "date-fns";
import { ptBR } from "date-fns/locale";
import { getDiscount } from '@/utils/planUtils';

interface Plan {
  id: number;
  name: string;
  slug: string;
  price: number;
  priceFormatted: string;
  discount_percentage?: number;
  duration_days?: number;
}

interface EditUserModalProps {
  user: User | null;
  isOpen: boolean;
  onClose: () => void;
  onSave: () => void;
  onUserChange: (user: User) => void;
}

const EditUserModal = ({ user, isOpen, onClose, onSave, onUserChange }: EditUserModalProps) => {
  const [plans, setPlans] = useState<Plan[]>([]);
  const [loadingPlans, setLoadingPlans] = useState(false);
  const [addPlanBalance, setAddPlanBalance] = useState(false);
  const [addPlanDays, setAddPlanDays] = useState(false);
  const [selectedPlanPrice, setSelectedPlanPrice] = useState(0);
  const [selectedPlanDays, setSelectedPlanDays] = useState(0);
  const [customDays, setCustomDays] = useState(0);
  const originalPlanBalanceRef = useRef<number>(0);
  const originalPlanStartRef = useRef<string | undefined>(undefined);
  const originalPlanEndRef = useRef<string | undefined>(undefined);

  useEffect(() => {
    if (isOpen && user) {
      fetchPlans();
      setAddPlanBalance(false);
      setAddPlanDays(false);
      setSelectedPlanPrice(0);
      setSelectedPlanDays(0);
      setCustomDays(0);
      originalPlanBalanceRef.current = user.planBalance || 0;
      originalPlanStartRef.current = user.planStartDate;
      originalPlanEndRef.current = user.planEndDate;
    }
  }, [isOpen]);

  const fetchPlans = async () => {
    setLoadingPlans(true);
    try {
      const response = await fetch(getFullApiUrl('/plans/active'));
      const result = await response.json();
      if (result.success && result.data) {
        setPlans(result.data);
      }
    } catch (error) {
      console.error('Erro ao carregar planos:', error);
    } finally {
      setLoadingPlans(false);
    }
  };

  if (!user) return null;

  const handlePlanChange = (value: string) => {
    const selectedPlan = plans.find(p => p.name === value);
    const discount = selectedPlan?.discount_percentage ?? getDiscount(value);
    const price = selectedPlan?.price || 0;
    const days = selectedPlan?.duration_days || 0;
    setSelectedPlanPrice(price);
    setSelectedPlanDays(days);
    setCustomDays(days);

    const newPlanBalance = addPlanBalance && price > 0
      ? originalPlanBalanceRef.current + price
      : originalPlanBalanceRef.current;

    const updates: Partial<User> = { plan: value, planDiscount: discount, planBalance: newPlanBalance };

    if (addPlanDays && days > 0) {
      const currentEndDate = originalPlanEndRef.current 
        ? new Date(originalPlanEndRef.current) 
        : new Date();
      updates.planEndDate = format(new Date(currentEndDate.getTime() + days * 86400000), 'yyyy-MM-dd');
    } else {
      updates.planStartDate = originalPlanStartRef.current;
      updates.planEndDate = originalPlanEndRef.current;
    }

    onUserChange({ ...user, ...updates } as User);
  };

  const handleToggleAddPlanBalance = (checked: boolean) => {
    setAddPlanBalance(checked);
    if (checked && selectedPlanPrice > 0) {
      onUserChange({ ...user, planBalance: originalPlanBalanceRef.current + selectedPlanPrice });
    } else {
      onUserChange({ ...user, planBalance: originalPlanBalanceRef.current });
    }
  };

  const handleToggleAddPlanDays = (checked: boolean) => {
    setAddPlanDays(checked);
    if (checked) {
      const days = customDays > 0 ? customDays : 30;
      setCustomDays(days);
      const currentEndDate = originalPlanEndRef.current 
        ? new Date(originalPlanEndRef.current) 
        : new Date();
      const newEndDate = new Date(currentEndDate.getTime() + days * 86400000);
      onUserChange({
        ...user,
        planEndDate: format(newEndDate, 'yyyy-MM-dd'),
      });
    } else {
      onUserChange({
        ...user,
        planEndDate: originalPlanEndRef.current,
      });
    }
  };

  const handleCustomDaysChange = (value: number) => {
    setCustomDays(value);
    if (addPlanDays && value > 0) {
      const currentEndDate = originalPlanEndRef.current 
        ? new Date(originalPlanEndRef.current) 
        : new Date();
      const newEndDate = new Date(currentEndDate.getTime() + value * 86400000);
      onUserChange({
        ...user,
        planEndDate: format(newEndDate, 'yyyy-MM-dd'),
      });
    }
  };

  const formatCurrency = (value: number) => {
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  };


  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="w-[95vw] max-w-2xl p-0 gap-0 overflow-hidden">
        {/* Header */}
        <DialogHeader className="px-5 pt-5 pb-4 border-b border-border bg-muted/30">
          <DialogTitle className="text-lg font-semibold flex items-center gap-2">
            <UserIcon className="h-5 w-5 text-primary" />
            Editar Usuário
          </DialogTitle>
          <p className="text-xs text-muted-foreground mt-1">{user.name} • {user.email}</p>
        </DialogHeader>

        <div className="p-5 space-y-5 max-h-[70vh] overflow-y-auto">
          {/* Seção: Dados Pessoais */}
          <div>
            <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Dados Pessoais</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="edit-name" className="text-xs flex items-center gap-1.5">
                  <UserIcon className="h-3 w-3" /> Nome Completo
                </Label>
                <Input
                  id="edit-name"
                  className="h-9 text-sm"
                  value={user.name}
                  onChange={(e) => onUserChange({ ...user, name: e.target.value })}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="edit-email" className="text-xs flex items-center gap-1.5">
                  <Mail className="h-3 w-3" /> E-mail
                </Label>
                <Input
                  id="edit-email"
                  className="h-9 text-sm"
                  value={user.email}
                  onChange={(e) => onUserChange({ ...user, email: e.target.value })}
                />
              </div>
            </div>
          </div>

          {/* Seção: Financeiro */}
          <div>
            <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Financeiro</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="edit-balance" className="text-xs flex items-center gap-1.5">
                  <CreditCard className="h-3 w-3" /> Saldo da Carteira
                </Label>
                <Input
                  id="edit-balance"
                  type="number"
                  step="0.01"
                  className="h-9 text-sm"
                  value={user.balance}
                  onChange={(e) => onUserChange({ ...user, balance: parseFloat(e.target.value) || 0 })}
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="edit-plan-balance" className="text-xs flex items-center gap-1.5">
                  <Wallet className="h-3 w-3" /> Saldo do Plano
                </Label>
                <Input
                  id="edit-plan-balance"
                  type="number"
                  step="0.01"
                  className="h-9 text-sm"
                  value={user.planBalance || 0}
                  onChange={(e) => {
                    const val = parseFloat(e.target.value) || 0;
                    originalPlanBalanceRef.current = val;
                    onUserChange({ ...user, planBalance: val });
                  }}
                />
              </div>
            </div>
          </div>

          {/* Seção: Plano */}
          <div>
            <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Plano</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="edit-plan" className="text-xs">Plano Atual</Label>
                {loadingPlans ? (
                  <div className="flex items-center gap-2 h-9 px-3 border rounded-md bg-muted">
                    <Loader2 className="h-3 w-3 animate-spin" />
                    <span className="text-xs text-muted-foreground">Carregando...</span>
                  </div>
                ) : (
                  <Select value={user.plan} onValueChange={handlePlanChange}>
                    <SelectTrigger id="edit-plan" className="h-9 text-sm">
                      <SelectValue placeholder="Selecione um plano" />
                    </SelectTrigger>
                    <SelectContent>
                      {plans.map((plan) => (
                        <SelectItem key={plan.id} value={plan.name}>
                          {plan.name} - {plan.priceFormatted}
                        </SelectItem>
                      ))}
                      {plans.length === 0 && (
                        <SelectItem value={user.plan} disabled={false}>
                          {user.plan}
                        </SelectItem>
                      )}
                    </SelectContent>
                  </Select>
                )}
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="edit-plan-discount" className="text-xs flex items-center gap-1.5">
                  <Percent className="h-3 w-3" /> Desconto (%)
                </Label>
                <Input
                  id="edit-plan-discount"
                  type="number"
                  min="0"
                  max="100"
                  className="h-9 text-sm"
                  value={user.planDiscount || 0}
                  onChange={(e) => onUserChange({ ...user, planDiscount: parseInt(e.target.value) || 0 })}
                />
              </div>
            </div>

            {/* Switches: Adicionar valor e dias */}
            <div className="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-border bg-muted/40">
                <div className="min-w-0">
                  <Label className="text-xs font-medium block">Adicionar valor ao saldo</Label>
                  <p className="text-[10px] text-muted-foreground truncate">
                    {selectedPlanPrice > 0 ? `Plano: ${formatCurrency(selectedPlanPrice)}` : 'Valor manual'}
                  </p>
                </div>
                <Switch
                  checked={addPlanBalance}
                  onCheckedChange={handleToggleAddPlanBalance}
                />
              </div>
              <div className="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-border bg-muted/40">
                <div className="min-w-0">
                  <Label className="text-xs font-medium block">Definir dias</Label>
                  <p className="text-[10px] text-muted-foreground truncate">
                    {selectedPlanDays > 0 ? `Plano: ${selectedPlanDays} dias` : 'Dias manual'}
                  </p>
                </div>
                <Switch
                  checked={addPlanDays}
                  onCheckedChange={handleToggleAddPlanDays}
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
              <div className="space-y-1.5">
                <Label className="text-xs flex items-center gap-1">
                  <Calendar className="h-3 w-3" /> Início
                </Label>
                <div className="h-9 text-sm px-3 flex items-center rounded-md border bg-muted text-foreground">
                  {user.planStartDate ? format(parseISO(user.planStartDate), 'dd/MM/yyyy', { locale: ptBR }) : '—'}
                </div>
              </div>
              <div className="space-y-1.5">
                <Label className="text-xs flex items-center gap-1">
                  <Calendar className="h-3 w-3" /> Término
                </Label>
                <div className="h-9 text-sm px-3 flex items-center rounded-md border bg-muted text-foreground">
                  {user.planEndDate ? format(parseISO(user.planEndDate), 'dd/MM/yyyy', { locale: ptBR }) : '—'}
                </div>
              </div>
              <div className="space-y-1.5">
                <Label className="text-xs flex items-center gap-1">
                  <Clock className="h-3 w-3" /> Dias a adicionar
                </Label>
                <Input
                  type="number"
                  min="0"
                  className="h-9 text-sm font-semibold text-primary"
                  value={addPlanDays ? customDays : 0}
                  onChange={(e) => handleCustomDaysChange(parseInt(e.target.value) || 0)}
                  disabled={!addPlanDays}
                />
              </div>
            </div>
          </div>

          {/* Seção: Observações */}
          <div>
            <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Observações</h3>
            <div className="space-y-1.5">
              <Label htmlFor="edit-notes" className="text-xs flex items-center gap-1.5">
                <FileText className="h-3 w-3" /> Notas
              </Label>
              <Input
                id="edit-notes"
                className="h-9 text-sm"
                value={user.notes || ''}
                onChange={(e) => onUserChange({ ...user, notes: e.target.value })}
                placeholder="Observação para o usuário..."
              />
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="flex justify-end gap-2 px-5 py-4 border-t border-border bg-muted/30">
          <Button size="sm" variant="outline" onClick={onClose} className="gap-1.5">
            <X className="h-3.5 w-3.5" />
            Cancelar
          </Button>
          <Button size="sm" onClick={onSave} className="gap-1.5">
            <Save className="h-3.5 w-3.5" />
            Salvar
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default EditUserModal;
